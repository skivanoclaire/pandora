<?php

namespace App\Services;

use App\Models\Staging\SyncDataChange;
use App\Models\Staging\SyncLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SimpegSyncService
{
    private array $syncConfigs;
    private string $initialSyncFrom = '2025-01-01';

    public function __construct()
    {
        $this->syncConfigs = $this->buildSyncConfigs();
    }

    public function syncAll(): array
    {
        $results = [];
        foreach ($this->syncConfigs as $config) {
            $results[$config['tabel_sumber']] = $this->syncTable($config);
        }
        return $results;
    }

    public function syncTable(array $config): array
    {
        $log = SyncLog::create([
            'tabel_sumber' => $config['tabel_sumber'],
            'tabel_tujuan' => $config['tabel_tujuan'],
            'started_at' => now(),
            'status' => 'running',
        ]);

        try {
            $lastSync = $this->getLastSuccessfulSync($config['tabel_tujuan']);
            $result = $this->pullDelta($config, $lastSync);

            $log->markSuccess($result['fetched'], $result['inserted'], $result['updated']);
            Log::info("Sync {$config['tabel_sumber']}: {$result['fetched']} fetched, {$result['inserted']} inserted, {$result['updated']} updated");

            return $result;
        } catch (\Throwable $e) {
            $log->markFailed($e->getMessage());
            Log::error("Sync {$config['tabel_sumber']} gagal: {$e->getMessage()}");
            return ['fetched' => 0, 'inserted' => 0, 'updated' => 0, 'error' => $e->getMessage()];
        }
    }

    private function pullDelta(array $config, ?\DateTimeInterface $since): array
    {
        $fetched = 0;
        $inserted = 0;
        $updated = 0;

        $query = DB::connection('simpeg')->table($config['tabel_sumber']);

        // Apply joins jika ada
        foreach ($config['joins'] ?? [] as $join) {
            $query->join($join['table'], $join['first'], '=', $join['second']);
        }

        // Apply WHERE filter jika ada
        foreach ($config['where'] ?? [] as $filter) {
            $query->where($filter[0], $filter[1], $filter[2]);
        }

        // Select hanya kolom sumber + pastikan distinct jika ada join
        $selectCols = array_map(
            fn ($col) => $config['tabel_sumber'] . '.' . $col,
            array_keys($config['kolom_map']),
        );
        // Tambah kolom dari join jika ada kolom_map_join
        foreach ($config['kolom_map_join'] ?? [] as $source => $target) {
            $selectCols[] = $source;
        }
        $query->select($selectCols);

        if ($since && $config['kolom_delta']) {
            $query->where($config['tabel_sumber'] . '.' . $config['kolom_delta'], '>', $since);
        } elseif (!$since && $config['initial_limit_col']) {
            $query->where($config['tabel_sumber'] . '.' . $config['initial_limit_col'], '>=', $this->initialSyncFrom);
        }

        // Delta sync by date window for tables without kolom_delta (e.g. present_rekap)
        $syncWindowDays = $config['sync_window_days'] ?? null;
        if ($syncWindowDays && $since) {
            $windowStart = now()->subDays($syncWindowDays)->toDateString();
            $dateCol = $config['initial_limit_col'] ?? 'tanggal';
            $query->where($config['tabel_sumber'] . '.' . $dateCol, '>=', $windowStart);
        }

        // Kolom tujuan untuk upsert
        $targetCols = array_merge(array_values($config['kolom_map']), array_values($config['kolom_map_join'] ?? []));
        $pkTujuan = $config['pk_tujuan'];

        $pkSumber = $config['tabel_sumber'] . '.' . $config['pk_sumber'];
        $query->orderBy($pkSumber)->chunk(1000, function ($rows) use ($config, $targetCols, $pkTujuan, &$fetched, &$inserted, &$updated) {
            $fetched += $rows->count();
            $now = now()->toDateTimeString();

            $batch = [];
            foreach ($rows as $row) {
                $mapped = $this->mapRow($row, $config);
                $mapped['synced_at'] = $now;
                $mapped['source_checksum'] = md5(json_encode((array) $row));
                $mapped['created_at'] = $now;
                $mapped['updated_at'] = $now;
                $batch[] = $mapped;
            }

            if (empty($batch)) {
                return;
            }

            // Batch upsert via INSERT ... ON CONFLICT
            $result = $this->batchUpsert($config['tabel_tujuan'], $batch, $pkTujuan, $config);
            $inserted += $result['inserted'];
            $updated += $result['updated'];
        });

        return ['fetched' => $fetched, 'inserted' => $inserted, 'updated' => $updated];
    }

    /**
     * Batch upsert menggunakan PostgreSQL INSERT ... ON CONFLICT DO UPDATE.
     * Jauh lebih cepat daripada per-row check + insert/update.
     */
    private function batchUpsert(string $table, array $batch, string $conflictCol, array $config = []): array
    {
        if (empty($batch)) {
            return ['inserted' => 0, 'updated' => 0];
        }

        $columns = array_keys($batch[0]);
        $updateCols = array_filter($columns, fn ($c) => !in_array($c, [$conflictCol, 'created_at']));

        $colList = '"' . implode('", "', $columns) . '"';
        $updateSet = implode(', ', array_map(fn ($c) => "\"$c\" = EXCLUDED.\"$c\"", $updateCols));

        $totalRows = 0;

        foreach (array_chunk($batch, 500) as $chunk) {
            // Get existing checksums before upsert for change detection
            $pks = array_column($chunk, $conflictCol);
            $existing = DB::table($table)
                ->whereIn($conflictCol, $pks)
                ->pluck('source_checksum', $conflictCol)
                ->toArray();

            $placeholders = [];
            $bindings = [];

            foreach ($chunk as $row) {
                $rowPlaceholders = [];
                foreach ($columns as $col) {
                    $rowPlaceholders[] = '?';
                    $bindings[] = $row[$col];
                }
                $placeholders[] = '(' . implode(', ', $rowPlaceholders) . ')';
            }

            $sql = "INSERT INTO \"{$table}\" ({$colList}) VALUES "
                . implode(', ', $placeholders)
                . " ON CONFLICT (\"{$conflictCol}\") DO UPDATE SET {$updateSet}";

            DB::statement($sql, $bindings);

            // Detect changes in historical data
            $this->detectDataChanges($config, $chunk, $existing, $conflictCol);

            $totalRows += count($chunk);
        }

        return ['inserted' => $totalRows, 'updated' => 0];
    }

    /**
     * Deteksi perubahan data historis yang mencurigakan.
     * Jika record lama (>30 hari) berubah checksum-nya, catat ke sync_data_changes.
     */
    private function detectDataChanges(array $config, array $chunk, array $existingChecksums, string $conflictCol): void
    {
        $thirtyDaysAgo = now()->subDays(30)->toDateString();
        $sixtyDaysAgo = now()->subDays(60)->toDateString();

        foreach ($chunk as $row) {
            $pk = $row[$conflictCol] ?? null;
            if (!$pk || !isset($existingChecksums[$pk])) {
                continue;
            }

            $oldChecksum = $existingChecksums[$pk];
            $newChecksum = $row['source_checksum'] ?? null;

            if ($oldChecksum === $newChecksum || !$oldChecksum || !$newChecksum) {
                continue;
            }

            // Determine record date
            $tanggal = $row['tanggal'] ?? null;
            if (!$tanggal) {
                continue;
            }

            // Only flag historical changes (older than 30 days)
            if ($tanggal >= $thirtyDaysAgo) {
                continue;
            }

            $severity = $tanggal < $sixtyDaysAgo ? 'critical' : 'warning';

            try {
                SyncDataChange::create([
                    'tabel_sumber' => $config['tabel_sumber'] ?? $config['tabel_tujuan'] ?? 'unknown',
                    'pk_value' => (string) $pk,
                    'tanggal' => $tanggal,
                    'old_checksum' => $oldChecksum,
                    'new_checksum' => $newChecksum,
                    'severity' => $severity,
                ]);
            } catch (\Throwable $e) {
                Log::warning("Failed to log data change: {$e->getMessage()}");
            }
        }
    }

    private function mapRow(object $row, array $config): array
    {
        $mapped = [];
        foreach ($config['kolom_map'] as $sumber => $tujuan) {
            $mapped[$tujuan] = $this->sanitizeValue($row->{$sumber} ?? null);
        }
        // Map kolom dari join tables
        foreach ($config['kolom_map_join'] ?? [] as $source => $target) {
            // Source bisa berupa "table.column", ambil alias-nya (bagian setelah titik)
            $alias = str_contains($source, '.') ? substr($source, strpos($source, '.') + 1) : $source;
            $mapped[$target] = $this->sanitizeValue($row->{$alias} ?? null);
        }
        return $mapped;
    }

    /**
     * Sanitasi nilai dari MySQL yang tidak kompatibel dengan PostgreSQL.
     * Contoh: tanggal "0000-00-00" atau "2020-10-00" invalid di PostgreSQL.
     */
    private function sanitizeValue(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        // Tanggal invalid MySQL: 0000-00-00, 2020-10-00, dsb
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value)) {
            // Cek apakah mengandung hari/bulan 00
            if (preg_match('/^\d{4}-00|^\d{4}-\d{2}-00/', $value)) {
                return null;
            }
            // Validasi tanggal sebenarnya
            $datePart = substr($value, 0, 10);
            if (!strtotime($datePart)) {
                return null;
            }
        }

        return $value;
    }

    private function getLastSuccessfulSync(string $tabelTujuan): ?\DateTimeInterface
    {
        $last = SyncLog::where('tabel_tujuan', $tabelTujuan)
            ->where('status', 'success')
            ->orderByDesc('finished_at')
            ->first();
        return $last?->started_at;
    }

    private function buildSyncConfigs(): array
    {
        return [
            [
                'tabel_sumber' => 'peg_pegawai',
                'tabel_tujuan' => 'sync_peg_pegawai',
                'pk_sumber' => 'id_pegawai',
                'pk_tujuan' => 'id_pegawai',
                'kolom_delta' => null,
                'initial_limit_col' => null,
                'kolom_map' => [
                    'id_pegawai' => 'id_pegawai', 'nip' => 'nip', 'nama' => 'nama',
                    'bebas_lokasi' => 'bebas_lokasi',
                ],
                'kolom_map_join' => [
                    'peg_jabatan.id_unit' => 'id_unit',
                    'peg_jabatan.id_status_pegawai' => 'status',
                ],
                'joins' => [
                    ['table' => 'peg_jabatan', 'first' => 'peg_pegawai.id_pegawai', 'second' => 'peg_jabatan.id_pegawai'],
                    ['table' => 'ref_status_pegawai', 'first' => 'peg_jabatan.id_status_pegawai', 'second' => 'ref_status_pegawai.id_status_pegawai'],
                    ['table' => 'ref_unit', 'first' => 'peg_jabatan.id_unit', 'second' => 'ref_unit.id_unit'],
                ],
                'where' => [
                    ['peg_jabatan.status', '=', 1],
                    ['ref_status_pegawai.tipe', '=', 1],
                    ['ref_unit.aktif', '=', 1],
                ],
            ],
            [
                'tabel_sumber' => 'ref_unit',
                'tabel_tujuan' => 'sync_ref_unit',
                'pk_sumber' => 'id_unit',
                'pk_tujuan' => 'id_unit',
                'kolom_delta' => null,
                'initial_limit_col' => null,
                'kolom_map' => [
                    'id_unit' => 'id_unit', 'unit' => 'nama_unit',
                    'id_par_unit' => 'parent_id', 'kode_unit' => 'kode_unit', 'level_unit' => 'level',
                ],
                'where' => [
                    ['aktif', '=', 1],
                ],
            ],
            [
                'tabel_sumber' => 'ref_unit_ext',
                'tabel_tujuan' => 'sync_ref_unit_ext',
                'pk_sumber' => 'id_unit_ext',
                'pk_tujuan' => 'id_unit_ext',
                'kolom_delta' => null,
                'initial_limit_col' => null,
                'kolom_map' => [
                    'id_unit_ext' => 'id_unit_ext', 'id_unit' => 'id_unit',
                ],
            ],
            [
                'tabel_sumber' => 'present_rekap',
                'tabel_tujuan' => 'sync_present_rekap',
                'pk_sumber' => 'id_rekap',
                'pk_tujuan' => 'id_rekap',
                'kolom_delta' => null,
                'initial_limit_col' => 'tanggal',
                'sync_window_days' => 45,
                'kolom_map' => [
                    'id_rekap' => 'id_rekap', 'id_pegawai' => 'id_pegawai', 'tanggal' => 'tanggal',
                    'lat_berangkat' => 'lat_berangkat', 'long_berangkat' => 'long_berangkat',
                    'nama_lokasi_berangkat' => 'nama_lokasi_berangkat', 'foto_berangkat' => 'foto_berangkat',
                    'lat_pulang' => 'lat_pulang', 'long_pulang' => 'long_pulang',
                    'nama_lokasi_pulang' => 'nama_lokasi_pulang', 'foto_pulang' => 'foto_pulang',
                    'masuk' => 'jam_masuk', 'pulang' => 'jam_pulang',
                    'tw' => 'tw', 'mkttw' => 'mkttw', 'pktw' => 'pktw', 'plc' => 'plc',
                    'tk' => 'tk', 'ta' => 'ta', 'i' => 'i', 's' => 's', 'c' => 'c',
                    'dl' => 'dl', 'dsp' => 'dsp', 'll' => 'll', 'd' => 'd',
                    'jenis_presensi' => 'jenis_presensi',
                ],
            ],
            [
                'tabel_sumber' => 'present_sikara_log',
                'tabel_tujuan' => 'sync_present_sikara_log',
                'pk_sumber' => 'id',
                'pk_tujuan' => 'id_log',
                'kolom_delta' => 'created_at',
                'initial_limit_col' => 'created_at',
                'kolom_map' => [
                    'id' => 'id_log', 'id_pegawai' => 'id_pegawai',
                    'data' => 'data', 'created_at' => 'cdate',
                ],
            ],
            [
                'tabel_sumber' => 'present_device',
                'tabel_tujuan' => 'sync_present_device',
                'pk_sumber' => 'id_present_device',
                'pk_tujuan' => 'id_device',
                'kolom_delta' => 'created_at',
                'initial_limit_col' => null,
                'kolom_map' => [
                    'id_present_device' => 'id_device', 'id_pegawai' => 'id_pegawai', 'imei_code' => 'imei',
                    'created_at' => 'cdate',
                ],
            ],
            [
                'tabel_sumber' => 'ref_lokasi_unit',
                'tabel_tujuan' => 'sync_ref_lokasi_unit',
                'pk_sumber' => 'id_ref_lokasi_unit',
                'pk_tujuan' => 'id_lokasi',
                'kolom_delta' => null,
                'initial_limit_col' => null,
                'kolom_map' => [
                    'id_ref_lokasi_unit' => 'id_lokasi', 'nama_lokasi' => 'nama_lokasi',
                    'latitude' => 'latitude', 'longitude' => 'longitude',
                    'jarak' => 'radius', 'aktif' => 'aktif',
                ],
            ],
            [
                'tabel_sumber' => 'ref_bantu_unit',
                'tabel_tujuan' => 'sync_ref_bantu_unit',
                'pk_sumber' => 'id_ref_bantu_unit',
                'pk_tujuan' => 'id_bantu',
                'kolom_delta' => null,
                'initial_limit_col' => null,
                'kolom_map' => [
                    'id_ref_bantu_unit' => 'id_bantu', 'id_unit' => 'id_unit', 'id_ref_lokasi_unit' => 'id_lokasi',
                ],
            ],
            [
                'tabel_sumber' => 'present_group',
                'tabel_tujuan' => 'sync_present_group',
                'pk_sumber' => 'id_group',
                'pk_tujuan' => 'id_group',
                'kolom_delta' => null,
                'initial_limit_col' => null,
                'kolom_map' => [
                    'id_group' => 'id_group', 'nama_group' => 'nama_group',
                    'berlaku' => 'berlaku', 'berakhir' => 'berakhir',
                    'sen_awal' => 'sen_awal', 'sen_akhir' => 'sen_akhir',
                    'sel_awal' => 'sel_awal', 'sel_akhir' => 'sel_akhir',
                    'rab_awal' => 'rab_awal', 'rab_akhir' => 'rab_akhir',
                    'kam_awal' => 'kam_awal', 'kam_akhir' => 'kam_akhir',
                    'jum_awal' => 'jum_awal', 'jum_akhir' => 'jum_akhir',
                    'sab_awal' => 'sab_awal', 'sab_akhir' => 'sab_akhir',
                    'min_awal' => 'min_awal', 'min_akhir' => 'min_akhir',
                ],
            ],
            [
                'tabel_sumber' => 'present_presensi',
                'tabel_tujuan' => 'sync_present_presensi',
                'pk_sumber' => 'id_pre',
                'pk_tujuan' => 'id_presensi',
                'kolom_delta' => 'cdate',
                'initial_limit_col' => null,
                'kolom_map' => [
                    'id_pre' => 'id_presensi', 'id_pegawai' => 'id_pegawai',
                    'id_group' => 'id_group', 'cdate' => 'cdate',
                ],
            ],
            [
                'tabel_sumber' => 'present_masuk',
                'tabel_tujuan' => 'sync_present_masuk',
                'pk_sumber' => 'id_masuk',
                'pk_tujuan' => 'id_masuk',
                'kolom_delta' => null,
                'initial_limit_col' => null,
                'kolom_map' => [
                    'id_masuk' => 'id_masuk', 'tanggal' => 'tanggal', 'id_group' => 'id_group',
                    'status' => 'status', 'masuk' => 'masuk', 'pulang' => 'pulang',
                ],
            ],
            [
                'tabel_sumber' => 'present_aturan',
                'tabel_tujuan' => 'sync_present_aturan',
                'pk_sumber' => 'id_aturan',
                'pk_tujuan' => 'id_aturan',
                'kolom_delta' => null,
                'initial_limit_col' => null,
                'kolom_map' => [
                    'id_aturan' => 'id_aturan', 'id_periode' => 'id_periode',
                    'id_tipe' => 'id_tipe', 'nilai' => 'nilai',
                ],
            ],
            [
                'tabel_sumber' => 'present_libur',
                'tabel_tujuan' => 'sync_present_libur',
                'pk_sumber' => 'id_libur',
                'pk_tujuan' => 'id_libur',
                'kolom_delta' => null,
                'initial_limit_col' => null,
                'kolom_map' => [
                    'id_libur' => 'id_libur', 'tanggal' => 'tanggal', 'keterangan' => 'keterangan',
                ],
            ],
            [
                'tabel_sumber' => 'present_ijin',
                'tabel_tujuan' => 'sync_present_ijin',
                'pk_sumber' => 'id_ijin',
                'pk_tujuan' => 'id_ijin',
                'kolom_delta' => 'cdate',
                'initial_limit_col' => null,
                'kolom_map' => [
                    'id_ijin' => 'id_ijin', 'id_pegawai' => 'id_pegawai',
                    'mulai' => 'tanggal_mulai', 'berakhir' => 'tanggal_selesai',
                    'tipe_ijin' => 'jenis_ijin', 'ket' => 'keterangan', 'cdate' => 'cdate',
                ],
            ],
            [
                'tabel_sumber' => 'present_maps_logs',
                'tabel_tujuan' => 'sync_present_maps_logs',
                'pk_sumber' => 'id_mlogs',
                'pk_tujuan' => 'id_maps_log',
                'kolom_delta' => null,
                'initial_limit_col' => 'tgl',
                'sync_window_days' => 45,
                'kolom_map' => [
                    'id_mlogs' => 'id_maps_log', 'id_pegawai' => 'id_pegawai',
                    'lat' => 'latitude', 'lang' => 'longitude',
                    'jam' => 'jam', 'jamke' => 'jamke', 'id_maps' => 'id_maps',
                    'tgl' => 'tanggal',
                ],
            ],
            [
                'tabel_sumber' => 'fake_gps',
                'tabel_tujuan' => 'sync_fake_gps',
                'pk_sumber' => 'id',
                'pk_tujuan' => 'id_fake_gps',
                'kolom_delta' => null,
                'initial_limit_col' => null,
                'kolom_map' => [
                    'id' => 'id_fake_gps', 'package_name' => 'package_name',
                ],
            ],
        ];
    }

    public function syncByName(string $tabelSumber): array
    {
        $config = collect($this->syncConfigs)->firstWhere('tabel_sumber', $tabelSumber);
        if (!$config) {
            throw new \InvalidArgumentException("Tabel sumber '$tabelSumber' tidak dikenali.");
        }
        return $this->syncTable($config);
    }

    public function availableTables(): array
    {
        return array_column($this->syncConfigs, 'tabel_sumber');
    }
}
