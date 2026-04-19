<?php

namespace App\Services;

use App\Models\Integrity\LogPresensiPandora;
use Illuminate\Support\Facades\DB;

class HashChainService
{
    /**
     * Kunci canonical payload — hanya kolom ini yang masuk perhitungan hash.
     */
    private const CANONICAL_KEYS = [
        'device_id',
        'id_pegawai',
        'latitude',
        'longitude',
        'nip',
        'source',
        'status',
        'waktu',
    ];

    /**
     * PostgreSQL advisory lock key untuk serialisasi append.
     */
    private const ADVISORY_LOCK_KEY = 7842911;

    /**
     * Insert batch rows ke log_presensi_pandora dengan hash chain.
     *
     * @param  array  $rows  Array of associative arrays (data presensi)
     * @return array  Inserted models
     */
    public function appendBatch(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        return DB::transaction(function () use ($rows) {
            // Advisory lock untuk thread safety
            DB::statement('SELECT pg_advisory_xact_lock(?)', [self::ADVISORY_LOCK_KEY]);

            // Ambil tail chain saat ini
            $tail = LogPresensiPandora::query()
                ->orderByDesc('sequence_no')
                ->first();

            $prevHash = $tail?->hash_current ?? str_repeat('0', 64);
            $sequenceNo = ($tail?->sequence_no ?? 0);

            $inserted = [];

            foreach ($rows as $row) {
                $sequenceNo++;

                $canonicalPayload = $this->canonicalPayload($row);
                $payloadHash = hash('sha256', $canonicalPayload);

                // hash_current = sha256(hash_prev + sha256(canonical_payload) + pack('J', sequence_no))
                $hashCurrent = hash('sha256', $prevHash . $payloadHash . pack('J', $sequenceNo));

                $row['hash_prev'] = $prevHash;
                $row['hash_current'] = $hashCurrent;
                $row['sequence_no'] = $sequenceNo;

                $model = LogPresensiPandora::create($row);
                $inserted[] = $model;

                $prevHash = $hashCurrent;
            }

            return $inserted;
        });
    }

    /**
     * Verifikasi integritas hash chain.
     *
     * @param  int|null  $from  sequence_no awal (inklusif)
     * @param  int|null  $to    sequence_no akhir (inklusif)
     * @return array  ['verified' => bool, 'broken' => array]
     */
    public function verifyChain(?int $from = null, ?int $to = null): array
    {
        $query = LogPresensiPandora::query()->orderBy('sequence_no');

        if ($from !== null) {
            $query->where('sequence_no', '>=', $from);
        }
        if ($to !== null) {
            $query->where('sequence_no', '<=', $to);
        }

        // Ambil hash_prev dari record sebelum $from (jika ada)
        $prevHash = str_repeat('0', 64);
        if ($from !== null && $from > 1) {
            $prev = LogPresensiPandora::where('sequence_no', $from - 1)->first();
            if ($prev) {
                $prevHash = $prev->hash_current;
            }
        }

        $broken = [];

        $query->chunk(1000, function ($records) use (&$prevHash, &$broken) {
            foreach ($records as $record) {
                $canonicalPayload = $this->canonicalPayload($record->toArray());
                $payloadHash = hash('sha256', $canonicalPayload);
                $expected = hash('sha256', $prevHash . $payloadHash . pack('J', $record->sequence_no));

                // Verifikasi hash_prev
                if ($record->hash_prev !== $prevHash) {
                    $broken[] = [
                        'sequence_no' => $record->sequence_no,
                        'reason' => 'hash_prev mismatch',
                        'expected_prev' => $prevHash,
                        'actual_prev' => $record->hash_prev,
                    ];
                }

                // Verifikasi hash_current
                if ($record->hash_current !== $expected) {
                    $broken[] = [
                        'sequence_no' => $record->sequence_no,
                        'reason' => 'hash_current mismatch',
                        'expected' => $expected,
                        'actual' => $record->hash_current,
                    ];
                }

                $prevHash = $record->hash_current;
            }
        });

        return [
            'verified' => empty($broken),
            'broken' => $broken,
        ];
    }

    /**
     * Buat canonical JSON payload dari row data.
     */
    private function canonicalPayload(array $row): string
    {
        $subset = [];
        foreach (self::CANONICAL_KEYS as $key) {
            $value = $row[$key] ?? null;

            // Normalisasi tipe data agar hash konsisten antara insert dan verify
            if ($key === 'waktu' && $value !== null) {
                // Selalu format YYYY-MM-DD HH:MM:SS
                $value = date('Y-m-d H:i:s', strtotime((string) $value));
            } elseif (in_array($key, ['latitude', 'longitude']) && $value !== null) {
                // Normalisasi ke 7 desimal (sesuai kolom decimal(10,7) di DB)
                $value = number_format((float) $value, 7, '.', '');
            } elseif ($key === 'id_pegawai' && $value !== null) {
                $value = (int) $value;
            }

            $subset[$key] = $value;
        }

        return json_encode($subset, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
