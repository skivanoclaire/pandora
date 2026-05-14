<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EvaluasiController extends Controller
{
    public function index(Request $request)
    {
        // Status review yang dianggap false positive (manual + auto-resolved by data correction).
        // CATATAN: 'policy_exception' TIDAK dihitung sebagai FP karena model BENAR mendeteksi
        // outlier — yang berlaku adalah pengecualian kebijakan (WFA, dinas luar, dispensasi).
        // Memasukkan policy_exception ke FP akan menyesatkan precision metric & feedback ML.
        $fpStatuses = ['false_positive', 'false_positive_resolved_by_status_update'];
        $reviewedStatuses = ['valid', 'false_positive', 'false_positive_resolved_by_status_update', 'policy_exception'];

        // =============================================
        // A. Metrik per metode deteksi
        // =============================================
        $perMetode = DB::table('anomaly_flags')
            ->selectRaw("
                metode_deteksi,
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status_review = 'valid') AS tp,
                COUNT(*) FILTER (WHERE status_review IN ('false_positive','false_positive_resolved_by_status_update')) AS fp,
                COUNT(*) FILTER (WHERE status_review = 'policy_exception') AS policy_exception,
                COUNT(*) FILTER (WHERE status_review = 'belum_direview') AS belum_direview,
                AVG(confidence) AS avg_confidence
            ")
            ->groupBy('metode_deteksi')
            ->orderByRaw("CASE metode_deteksi WHEN 'rule_engine' THEN 1 WHEN 'isolation_forest' THEN 2 WHEN 'dbscan' THEN 3 ELSE 4 END")
            ->get()
            ->map(fn ($r) => $this->computeMetrics($r));

        // =============================================
        // B. Metrik per tingkat anomali
        // =============================================
        $perTingkat = DB::table('anomaly_flags')
            ->selectRaw("
                tingkat,
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status_review = 'valid') AS tp,
                COUNT(*) FILTER (WHERE status_review IN ('false_positive','false_positive_resolved_by_status_update')) AS fp,
                COUNT(*) FILTER (WHERE status_review = 'policy_exception') AS policy_exception,
                COUNT(*) FILTER (WHERE status_review = 'belum_direview') AS belum_direview,
                AVG(confidence) AS avg_confidence
            ")
            ->groupBy('tingkat')
            ->orderBy('tingkat')
            ->get()
            ->map(fn ($r) => $this->computeMetrics($r));

        // =============================================
        // C. Overall
        // =============================================
        $overall = DB::table('anomaly_flags')
            ->selectRaw("
                'overall' AS label,
                COUNT(*) AS total,
                COUNT(*) FILTER (WHERE status_review = 'valid') AS tp,
                COUNT(*) FILTER (WHERE status_review IN ('false_positive','false_positive_resolved_by_status_update')) AS fp,
                COUNT(*) FILTER (WHERE status_review = 'policy_exception') AS policy_exception,
                COUNT(*) FILTER (WHERE status_review = 'belum_direview') AS belum_direview,
                AVG(confidence) AS avg_confidence
            ")
            ->first();
        $overall = $this->computeMetrics($overall);

        // =============================================
        // D. Ground truth sample (20 terbaru yang sudah direview)
        // =============================================
        $groundTruth = DB::table('anomaly_flags as af')
            ->join('sync_peg_pegawai as p', 'p.id_pegawai', '=', 'af.id_pegawai')
            ->leftJoin('users as u', 'u.id', '=', 'af.direview_oleh')
            ->whereIn('af.status_review', $reviewedStatuses)
            ->select(
                'af.id', 'af.tanggal', 'p.nip', 'p.nama',
                'af.metode_deteksi', 'af.jenis_anomali', 'af.tingkat',
                'af.confidence', 'af.status_review',
                'u.name as reviewer', 'af.direview_pada',
            )
            ->orderByDesc('af.direview_pada')
            ->paginate(20);

        return view('analitik.evaluasi', compact(
            'perMetode', 'perTingkat', 'overall', 'groundTruth',
        ));
    }

    private function computeMetrics(object $row): object
    {
        $tp = (int) $row->tp;
        $fp = (int) $row->fp;
        $policyException = (int) ($row->policy_exception ?? 0);
        $reviewed = $tp + $fp;
        $total = (int) $row->total;
        $belum = (int) $row->belum_direview;

        // Precision: dari yang direview (valid + false_positive). Policy exception
        // dikeluarkan karena bukan kegagalan model.
        $precision = $reviewed > 0 ? $tp / $reviewed : null;

        // Recall estimasi: TP / total anomali yang relevan untuk evaluasi model.
        // Policy exception dikeluarkan dari denominator: deteksi-nya benar tapi
        // bukan "pelanggaran yang seharusnya ditangkap" → bukan FN juga.
        $totalEvaluable = max($total - $policyException, 0);
        $recall = $totalEvaluable > 0 ? $tp / $totalEvaluable : null;

        $f1 = ($precision !== null && $recall !== null && ($precision + $recall) > 0)
            ? 2 * $precision * $recall / ($precision + $recall)
            : null;

        $row->reviewed = $reviewed;
        $row->policy_exception = $policyException;
        $row->total_evaluable = $totalEvaluable;
        $row->precision = $precision;
        $row->recall = $recall;
        $row->f1 = $f1;
        $row->review_pct = $total > 0 ? $reviewed / $total * 100 : 0;
        $row->avg_confidence = $row->avg_confidence ? round((float) $row->avg_confidence, 4) : null;

        return $row;
    }
}
