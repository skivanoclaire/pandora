<?php

namespace App\Console\Commands;

use App\Models\Integrity\LedgerAnchor;
use App\Models\Integrity\LogPresensiPandora;
use App\Services\MerkleTreeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class LedgerAnchorDaily extends Command
{
    protected $signature = 'ledger:anchor-daily {--date= : Tanggal anchor (default: hari ini)}';
    protected $description = 'Buat Merkle root harian dan kirim ke anchor service untuk timestamping Bitcoin';

    public function handle(MerkleTreeService $merkle): int
    {
        $date = $this->option('date') ?? now()->toDateString();

        $this->info("Anchoring data tanggal: {$date}");

        // Ambil semua record untuk tanggal ini
        $hashes = LogPresensiPandora::whereDate('waktu', $date)
            ->orderBy('sequence_no')
            ->pluck('hash_current')
            ->toArray();

        if (empty($hashes)) {
            $this->warn("Tidak ada record untuk tanggal {$date}. Anchor dibatalkan.");
            return self::SUCCESS;
        }

        $this->info("Jumlah record: " . count($hashes));

        // Hitung Merkle root
        $merkleRoot = $merkle->root($hashes);
        $this->info("Merkle root: {$merkleRoot}");

        // Simpan ke ledger_anchor dengan status pending
        $anchor = LedgerAnchor::create([
            'tanggal' => $date,
            'merkle_root' => $merkleRoot,
            'jumlah_record' => count($hashes),
            'sequence_start' => LogPresensiPandora::whereDate('waktu', $date)->min('sequence_no'),
            'sequence_end' => LogPresensiPandora::whereDate('waktu', $date)->max('sequence_no'),
            'status' => 'pending',
        ]);

        // Kirim ke anchor service
        try {
            $anchorUrl = env('ANCHOR_SERVICE_URL', 'http://pandora-anchor:8700');
            $response = Http::timeout(60)->post("{$anchorUrl}/anchor", [
                'date' => $date,
                'merkle_root_hex' => $merkleRoot,
            ]);

            if ($response->failed()) {
                $this->error("Anchor service gagal: " . $response->body());
                $anchor->update(['status' => 'failed']);
                return self::FAILURE;
            }

            $data = $response->json();

            $anchor->update([
                'ots_proof_incomplete' => $data['ots_proof_base64'] ?? null,
                'status' => 'anchored',
                'anchored_at' => now(),
            ]);

            $this->info("Anchor berhasil. Status: anchored");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            $anchor->update(['status' => 'failed']);
            return self::FAILURE;
        }
    }
}
