<?php

namespace App\Console\Commands;

use App\Models\Integrity\LogPresensiPandora;
use App\Services\HashChainService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class LogPresensiSync extends Command
{
    protected $signature = 'ledger:sync-presensi {--date= : Tanggal spesifik (YYYY-MM-DD)} {--days=2 : Jumlah hari ke belakang}';
    protected $description = 'Salin event log presensi (check-in/check-out) ke log_presensi_pandora (append-only + hash chain)';

    public function handle(HashChainService $hashChain): int
    {
        $date = $this->option('date');
        $days = (int) $this->option('days');

        if ($date) {
            $startDate = $date;
            $endDate = $date;
        } else {
            $endDate = now()->toDateString();
            $startDate = now()->subDays($days)->toDateString();
        }

        $this->info("Sync log_presensi_pandora dari present_sikara_log: {$startDate} s/d {$endDate}");

        // Get existing id_log yang sudah di-sync untuk date range ini (cegah duplikat)
        $existingLogs = DB::table('log_presensi_pandora')
            ->where('source', 'sikara_log')
            ->whereNotNull('metadata')
            ->whereBetween(DB::raw("waktu::date"), [$startDate, $endDate])
            ->selectRaw("metadata->>'id_log' as id_log")
            ->pluck('id_log')
            ->filter()
            ->flip()
            ->toArray();

        $this->info("  Existing records: " . count($existingLogs));

        // Pull dari sync_present_sikara_log — event mentah check-in/check-out
        $query = DB::table('sync_present_sikara_log as l')
            ->leftJoin('sync_peg_pegawai as p', 'l.id_pegawai', '=', 'p.id_pegawai')
            ->whereBetween(DB::raw('l.cdate::date'), [$startDate, $endDate])
            ->whereNotNull('l.data')
            ->select(['l.id_log', 'l.id_pegawai', 'p.nip', 'l.data', 'l.cdate'])
            ->orderBy('l.cdate')
            ->orderBy('l.id_log');

        $totalInserted = 0;
        $totalSkipped = 0;
        $batch = [];

        $query->chunk(500, function ($rows) use ($hashChain, &$batch, &$totalInserted, &$totalSkipped, $existingLogs) {
            foreach ($rows as $row) {
                // Skip duplikat
                if (isset($existingLogs[(string) $row->id_log])) {
                    $totalSkipped++;
                    continue;
                }

                $data = json_decode($row->data, true);
                if (!$data) {
                    continue;
                }

                // Extract lat/long dan waktu dari payload JSON
                $isCheckIn = isset($data['masuk']);
                $latitude = $isCheckIn
                    ? ($data['lat_berangkat'] ?? null)
                    : ($data['lat_pulang'] ?? null);
                $longitude = $isCheckIn
                    ? ($data['long_berangkat'] ?? null)
                    : ($data['long_pulang'] ?? null);
                $waktu = ($data['tanggal'] ?? substr($row->cdate, 0, 10))
                    . ' '
                    . ($isCheckIn ? ($data['masuk'] ?? '00:00:00') : ($data['pulang'] ?? '00:00:00'));
                $status = $isCheckIn ? 'check_in' : 'check_out';
                $lokasiNama = $isCheckIn
                    ? ($data['nama_lokasi_berangkat'] ?? null)
                    : ($data['nama_lokasi_pulang'] ?? null);

                $batch[] = [
                    'id_pegawai' => $row->id_pegawai,
                    'nip' => $row->nip ?? ($data['id_pegawai'] ?? ''),
                    'waktu' => $waktu,
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'status' => $status,
                    'device_id' => null,
                    'source' => 'sikara_log',
                    'metadata' => json_encode([
                        'id_log' => $row->id_log,
                        'lokasi' => $lokasiNama,
                        'jenis_presensi' => $data['jenis_presensi'] ?? null,
                    ]),
                ];

                if (count($batch) >= 200) {
                    $hashChain->appendBatch($batch);
                    $totalInserted += count($batch);
                    $batch = [];

                    if ($totalInserted % 5000 === 0) {
                        $this->output->write("\r  Inserted: {$totalInserted}");
                    }
                }
            }
        });

        // Flush remaining
        if (!empty($batch)) {
            $hashChain->appendBatch($batch);
            $totalInserted += count($batch);
        }

        $this->info("\n  Total inserted: {$totalInserted}, skipped (duplikat): {$totalSkipped}");
        return self::SUCCESS;
    }
}
