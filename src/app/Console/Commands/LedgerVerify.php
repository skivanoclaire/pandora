<?php

namespace App\Console\Commands;

use App\Services\HashChainService;
use Illuminate\Console\Command;

class LedgerVerify extends Command
{
    protected $signature = 'ledger:verify {--from= : Sequence number awal} {--to= : Sequence number akhir}';
    protected $description = 'Verifikasi integritas hash chain log_presensi_pandora';

    public function handle(HashChainService $hashChain): int
    {
        $from = $this->option('from') ? (int) $this->option('from') : null;
        $to = $this->option('to') ? (int) $this->option('to') : null;

        $range = match (true) {
            $from !== null && $to !== null => "sequence {$from} s/d {$to}",
            $from !== null => "sequence {$from} s/d akhir",
            $to !== null => "sequence awal s/d {$to}",
            default => "seluruh chain",
        };

        $this->info("Verifikasi hash chain: {$range}");
        $this->newLine();

        $result = $hashChain->verifyChain($from, $to);

        if ($result['verified']) {
            $this->info('Hash chain VALID. Tidak ditemukan kerusakan.');
            return self::SUCCESS;
        }

        $this->error('Hash chain RUSAK! Ditemukan ' . count($result['broken']) . ' record bermasalah:');
        $this->newLine();

        foreach ($result['broken'] as $entry) {
            $this->warn("  Sequence #{$entry['sequence_no']}: {$entry['reason']}");
        }

        return self::FAILURE;
    }
}
