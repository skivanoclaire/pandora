<?php

namespace App\Console\Commands;

use App\Models\Integrity\LedgerAnchor;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class LedgerAnchorUpgrade extends Command
{
    protected $signature = 'ledger:anchor-upgrade';
    protected $description = 'Upgrade OTS proof yang belum confirmed ke Bitcoin blockchain';

    public function handle(): int
    {
        $anchors = LedgerAnchor::anchored()->get();

        if ($anchors->isEmpty()) {
            $this->info('Tidak ada anchor yang perlu di-upgrade.');
            return self::SUCCESS;
        }

        $this->info("Memproses {$anchors->count()} anchor...");

        $upgraded = 0;
        $failed = 0;

        foreach ($anchors as $anchor) {
            $this->line("  Upgrade anchor tanggal {$anchor->tanggal}...");

            try {
                $response = Http::timeout(60)->post('http://pandora-anchor:8700/upgrade', [
                    'ots_proof_base64' => $anchor->ots_proof_incomplete,
                ]);

                if ($response->failed()) {
                    $this->warn("    Gagal: " . $response->body());
                    $failed++;
                    continue;
                }

                $data = $response->json();

                if (($data['upgraded'] ?? false) === true) {
                    $anchor->update([
                        'ots_proof_complete' => $data['ots_proof_base64'] ?? null,
                        'btc_block_hash' => $data['btc_block_hash'] ?? null,
                        'btc_block_height' => $data['btc_block_height'] ?? null,
                        'status' => 'confirmed',
                        'confirmed_at' => now(),
                    ]);
                    $this->info("    Confirmed di block {$data['btc_block_height']}");
                    $upgraded++;
                } else {
                    $this->line("    Belum confirmed, menunggu...");
                }
            } catch (\Exception $e) {
                $this->warn("    Error: " . $e->getMessage());
                $failed++;
            }
        }

        $this->newLine();
        $this->info("Selesai. Upgraded: {$upgraded}, Failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
