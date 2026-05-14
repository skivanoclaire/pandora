<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $backfillNote = ' [Reklasifikasi 2026-05-14: dipindah ke policy_exception — model benar mendeteksi outlier, kebijakan mengizinkan]';

        // 1. WFA records currently 'false_positive' (manual labeling) → policy_exception
        //    Match by joining sync_present_rekap dan cek nama_lokasi berangkat/pulang
        DB::statement("
            UPDATE anomaly_flags af
            SET status_review = 'policy_exception',
                catatan_review = COALESCE(af.catatan_review, '') || ?,
                updated_at = NOW()
            FROM sync_present_rekap spr
            WHERE af.id_pegawai = spr.id_pegawai
              AND af.tanggal = spr.tanggal
              AND af.status_review = 'false_positive'
              AND (
                UPPER(COALESCE(spr.nama_lokasi_berangkat, '')) IN ('WORK FROM ANYWHERE', 'W F A')
                OR UPPER(COALESCE(spr.nama_lokasi_pulang, '')) IN ('WORK FROM ANYWHERE', 'W F A')
              )
        ", [$backfillNote]);

        // 2. Auto-resolved WFA (false_positive_resolved_by_status_update + catatan WFA) → policy_exception
        DB::statement("
            UPDATE anomaly_flags
            SET status_review = 'policy_exception',
                updated_at = NOW()
            WHERE status_review = 'false_positive_resolved_by_status_update'
              AND catatan_review LIKE 'Auto-resolved: hari WFA%'
        ");

        // 3. Auto-resolved libur nasional sukarela → policy_exception
        //    (pegawai hadir sukarela di hari libur — model benar deteksi outlier temporal,
        //    kebijakan/realita: pegawai memang boleh hadir di libur)
        DB::statement("
            UPDATE anomaly_flags
            SET status_review = 'policy_exception',
                updated_at = NOW()
            WHERE status_review = 'false_positive_resolved_by_status_update'
              AND catatan_review LIKE 'Auto-resolved: absensi di hari libur nasional%'
        ");
    }

    public function down(): void
    {
        // Rollback: kembalikan ke false_positive_resolved_by_status_update (paling dekat secara semantik)
        // Catatan: down() ini tidak bisa membedakan WFA manual vs WFA auto karena kita sudah merge mereka.
        DB::statement("
            UPDATE anomaly_flags
            SET status_review = 'false_positive_resolved_by_status_update',
                updated_at = NOW()
            WHERE status_review = 'policy_exception'
        ");
    }
};
