<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PipelineDaily extends Command
{
    protected $signature = 'pipeline:daily {--date= : Tanggal yang diproses (default: kemarin)}';
    protected $description = 'Jalankan pipeline analitik harian (Tingkat 1) via FastAPI';

    public function handle(): int
    {
        $date = $this->option('date') ?? now()->subDay()->toDateString();
        $url = config('services.analytics.url', env('ANALYTICS_SERVICE_URL', 'http://pandora-analytics:8000'));

        $this->info("Menjalankan pipeline harian untuk tanggal: {$date}");

        try {
            $response = Http::timeout(300)->post("{$url}/pipeline/daily", [
                'tanggal' => $date,
            ]);

            if ($response->failed()) {
                $this->error("Pipeline gagal: " . $response->body());
                return self::FAILURE;
            }

            $data = $response->json();

            $this->newLine();
            $this->info("Pipeline selesai dalam {$data['duration_seconds']}s");
            $this->newLine();

            if ($fe = $data['feature_engineering'] ?? null) {
                $this->line("  Feature Engineering: {$fe['total']} rekap, {$fe['inserted']} inserted");
            }
            if ($re = $data['rule_engine'] ?? null) {
                $this->line("  Rule Engine T1: {$re['total_checked']} checked, {$re['anomalies_found']} anomali");
            }
            if ($ifo = $data['isolation_forest'] ?? null) {
                $reason = $ifo['reason'] ?? 'N/A';
                $this->line("  Isolation Forest: " . ($ifo['status'] === 'completed'
                    ? "{$ifo['anomalies_detected']} detected, {$ifo['anomalies_inserted']} inserted"
                    : "skipped ({$reason})"));
            }
            if ($dbs = $data['dbscan'] ?? null) {
                $reason = $dbs['reason'] ?? 'N/A';
                $this->line("  DBSCAN: " . ($dbs['status'] === 'completed'
                    ? "{$dbs['n_clusters']} clusters, {$dbs['noise_points']} noise, {$dbs['anomalies_inserted']} inserted"
                    : "skipped ({$reason})"));
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
