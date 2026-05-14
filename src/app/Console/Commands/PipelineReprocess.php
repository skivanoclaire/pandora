<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PipelineReprocess extends Command
{
    protected $signature = 'pipeline:reprocess
        {--from= : Tanggal awal (YYYY-MM-DD)}
        {--to= : Tanggal akhir (YYYY-MM-DD)}
        {--keep-anomali : Jangan hapus anomali rule engine lama}';

    protected $description = 'Hapus anomali lama dan jalankan ulang pipeline (setelah perbaikan rule engine)';

    public function handle(): int
    {
        $from = $this->option('from');
        $to = $this->option('to');

        if (!$from || !$to) {
            $this->error('Harus menyertakan --from dan --to. Contoh: --from=2026-04-01 --to=2026-04-30');
            return self::FAILURE;
        }

        $hapusAnomali = !$this->option('keep-anomali');
        $url = config('services.analytics.url', env('ANALYTICS_SERVICE_URL', 'http://pandora-analytics:8000'));

        $this->info("Reprocess pipeline: {$from} s/d {$to}");
        if ($hapusAnomali) {
            $this->warn("Anomali rule engine lama dalam rentang ini akan DIHAPUS dan dievaluasi ulang.");
        }

        if (!$this->confirm('Lanjutkan?')) {
            return self::SUCCESS;
        }

        try {
            $response = Http::timeout(1800)->post("{$url}/pipeline/reprocess", [
                'tanggal_awal' => $from,
                'tanggal_akhir' => $to,
                'hapus_anomali_rule_engine' => $hapusAnomali,
            ]);

            if ($response->failed()) {
                $this->error("Pipeline gagal: " . $response->body());
                return self::FAILURE;
            }

            $data = $response->json();

            $this->newLine();
            $this->info("Reprocess selesai dalam {$data['duration_seconds']}s");
            $this->newLine();

            if ($fe = $data['feature_engineering'] ?? null) {
                $this->line("  Feature Engineering: {$fe['total']} rekap, {$fe['inserted']} inserted, {$fe['skipped']} updated");
            }
            if ($re = $data['rule_engine'] ?? null) {
                $deleted = $re['anomali_lama_dihapus'] ?? 0;
                $this->line("  Anomali lama dihapus: {$deleted}");
                $this->line("  Rule Engine T1: {$re['total_checked']} checked, {$re['anomalies_found']} anomali baru");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
