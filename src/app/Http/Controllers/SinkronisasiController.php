<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Integrity\AuditTrail;

class SinkronisasiController extends Controller
{
    public function index()
    {
        // Latest sync per table
        $latestSyncs = DB::table('sync_log as s1')
            ->where('s1.id', function ($q) {
                $q->selectRaw('MAX(id)')->from('sync_log as s2')
                  ->whereColumn('s2.tabel_sumber', 's1.tabel_sumber');
            })
            ->orderBy('tabel_sumber')
            ->get();

        // Recent sync history (last 50)
        $recentSyncs = DB::table('sync_log')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        // Data change alerts
        $dataChanges = DB::table('sync_data_changes')
            ->where('reviewed', false)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        $changeStats = [
            'total' => DB::table('sync_data_changes')->where('reviewed', false)->count(),
            'critical' => DB::table('sync_data_changes')->where('reviewed', false)->where('severity', 'critical')->count(),
            'warning' => DB::table('sync_data_changes')->where('reviewed', false)->where('severity', 'warning')->count(),
        ];

        AuditTrail::catat(auth()->id(), 'lihat_sinkronisasi', 'sync_log', null, null);

        return view('sinkronisasi.index', compact('latestSyncs', 'recentSyncs', 'dataChanges', 'changeStats'));
    }
}
