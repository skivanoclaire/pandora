<?php

namespace App\Console\Commands;

use App\Services\SimpegSyncService;
use Illuminate\Console\Command;

class SimpegSync extends Command
{
    protected $signature = 'simpeg:sync {--table= : Sync tabel tertentu saja (nama tabel sumber SIKARA)}';
    protected $description = 'Sinkronisasi data dari SIMPEG/SIKARA ke staging tables PANDORA';

    public function handle(SimpegSyncService $service): int
    {
        $table = $this->option('table');

        if ($table) {
            $this->info("Memulai sync tabel: {$table}");
            $result = $service->syncByName($table);
            $this->printResult($table, $result);
        } else {
            $this->info('Memulai sinkronisasi semua tabel SIKARA...');
            $this->newLine();

            $results = $service->syncAll();

            foreach ($results as $tabel => $result) {
                $this->printResult($tabel, $result);
            }
        }

        return self::SUCCESS;
    }

    private function printResult(string $tabel, array $result): void
    {
        if (isset($result['error'])) {
            $this->error("  [{$tabel}] GAGAL: {$result['error']}");
        } else {
            $this->line("  [{$tabel}] fetched={$result['fetched']} inserted={$result['inserted']} updated={$result['updated']}");
        }
    }
}
