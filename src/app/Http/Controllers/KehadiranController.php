<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class KehadiranController extends Controller
{
    public function rekap(Request $request)
    {
        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());
        $unitFilter = $request->get('unit');

        $query = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('r.tanggal', $tanggal)
            ->select([
                'p.nip', 'p.nama', 'u.nama_unit',
                'r.jam_masuk', 'r.jam_pulang',
                'r.tw', 'r.mkttw', 'r.pktw', 'r.plc', 'r.tk', 'r.ta',
                'r.i', 'r.s', 'r.c', 'r.dl', 'r.dsp', 'r.ll',
                'r.nama_lokasi_berangkat', 'r.nama_lokasi_pulang',
            ]);

        if ($unitFilter) {
            $query->where('u.id_unit', $unitFilter);
        }

        $rekap = $query->orderBy('u.nama_unit')->orderBy('p.nama')->paginate(50);

        // Ringkasan
        // Jam masuk jadwal aktif (fallback jika tw/mkttw NULL)
        $jadwal = DB::table('sync_present_group')
            ->whereRaw('berlaku <= CURRENT_DATE AND berakhir >= CURRENT_DATE')
            ->first();
        $jamMasukJadwal = $jadwal->sen_awal ?? '07:30:00';

        $summary = DB::table('sync_present_rekap')
            ->where('tanggal', $tanggal)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN COALESCE(tw, 0) = 1 THEN 1 WHEN jam_masuk IS NOT NULL AND jam_masuk <= '{$jamMasukJadwal}' THEN 1 ELSE 0 END) as tw,
                SUM(CASE WHEN COALESCE(mkttw, 0) = 1 THEN 1 WHEN jam_masuk IS NOT NULL AND jam_masuk > '{$jamMasukJadwal}' THEN 1 ELSE 0 END) as mkttw,
                SUM(CASE WHEN COALESCE(tk, 0) = 1 THEN 1 WHEN jam_masuk IS NULL THEN 1 ELSE 0 END) as tk,
                SUM(CASE WHEN COALESCE(ta, 0) = 1 THEN 1 ELSE 0 END) as ta,
                SUM(CASE WHEN COALESCE(i, 0) = 1 THEN 1 ELSE 0 END) as izin,
                SUM(CASE WHEN COALESCE(s, 0) = 1 THEN 1 ELSE 0 END) as sakit,
                SUM(CASE WHEN COALESCE(c, 0) = 1 THEN 1 ELSE 0 END) as cuti,
                SUM(CASE WHEN COALESCE(dl, 0) = 1 THEN 1 ELSE 0 END) as dl,
                SUM(CASE WHEN COALESCE(dsp, 0) = 1 THEN 1 ELSE 0 END) as dsp
            ")->first();

        $units = DB::table('sync_ref_unit')->orderBy('nama_unit')->get(['id_unit', 'nama_unit']);

        return view('kehadiran.rekap', compact('rekap', 'summary', 'tanggal', 'units', 'unitFilter'));
    }

    public function log(Request $request)
    {
        $tanggal = $request->get('tanggal', Carbon::today()->toDateString());
        $search = $request->get('search');

        $query = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('r.tanggal', $tanggal)
            ->select([
                'p.nip', 'p.nama', 'u.nama_unit',
                'r.jam_masuk', 'r.jam_pulang',
                'r.lat_berangkat', 'r.long_berangkat', 'r.nama_lokasi_berangkat',
                'r.lat_pulang', 'r.long_pulang', 'r.nama_lokasi_pulang',
                'r.tw', 'r.mkttw', 'r.tk', 'r.ta', 'r.dl', 'r.dsp',
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('p.nama', 'ilike', "%{$search}%")
                  ->orWhere('p.nip', 'like', "%{$search}%");
            });
        }

        $logs = $query->orderBy('r.jam_masuk')->paginate(50);

        return view('kehadiran.log', compact('logs', 'tanggal', 'search'));
    }
}
