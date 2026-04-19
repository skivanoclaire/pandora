<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PipelineMonthly extends Command
{
    protected $signature = 'pipeline:monthly {--month= : Bulan (1-12)} {--year= : Tahun}';
    protected $description = 'Jalankan pipeline analitik bulanan (Tingkat 2+3) via FastAPI';

    public function handle(): int
    {
        $month = (int) ($this->option('month') ?? now()->subMonth()->month);
        $year = (int) ($this->option('year') ?? now()->subMonth()->year);
        $url = config('services.analytics.url', env('ANALYTICS_SERVICE_URL', 'http://pandora-analytics:8000'));

        $this->info("Menjalankan pipeline bulanan untuk: {$year}-{$month}");

        try {
            $response = Http::timeout(600)->post("{$url}/pipeline/monthly", [
                'bulan' => $month,
                'tahun' => $year,
            ]);

            if ($response->failed()) {
                $this->error("Pipeline gagal: " . $response->body());
                return self::FAILURE;
            }

            $data = $response->json();

            $this->newLine();
            $this->info("Pipeline bulanan selesai dalam {$data['duration_seconds']}s");
            $this->newLine();

            if ($fe = $data['feature_engineering'] ?? null) {
                $this->line("  Feature Engineering: {$fe['total']} rekap, {$fe['inserted']} inserted");
            }
            if ($re = $data['rule_engine'] ?? null) {
                $this->line("  Rule Engine T2: {$re['anomalies_found']} anomali");
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
                    ? "{$dbs['n_clusters']} clusters, {$dbs['noise_points']} noise"
                    : "skipped ({$reason})"));
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
