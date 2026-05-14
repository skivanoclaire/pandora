<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE anomaly_flags DROP CONSTRAINT IF EXISTS anomaly_flags_status_review_check");
        DB::statement("ALTER TABLE anomaly_flags ADD CONSTRAINT anomaly_flags_status_review_check CHECK (status_review::text = ANY (ARRAY['belum_direview', 'valid', 'false_positive', 'false_positive_resolved_by_status_update', 'policy_exception']::varchar[]))");
    }

    public function down(): void
    {
        DB::statement("UPDATE anomaly_flags SET status_review = 'false_positive_resolved_by_status_update' WHERE status_review = 'policy_exception'");
        DB::statement("ALTER TABLE anomaly_flags DROP CONSTRAINT IF EXISTS anomaly_flags_status_review_check");
        DB::statement("ALTER TABLE anomaly_flags ADD CONSTRAINT anomaly_flags_status_review_check CHECK (status_review::text = ANY (ARRAY['belum_direview', 'valid', 'false_positive', 'false_positive_resolved_by_status_update']::varchar[]))");
    }
};
