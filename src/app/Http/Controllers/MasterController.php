<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterController extends Controller
{
    public function instansi(Request $request)
    {
        $search = $request->get('search');
        $level = $request->get('level');

        $query = DB::table('sync_ref_unit as u')
            ->leftJoin(DB::raw('(SELECT id_unit, COUNT(*) as jml_pegawai FROM sync_peg_pegawai GROUP BY id_unit) as pc'), 'u.id_unit', '=', 'pc.id_unit')
            ->select(['u.id_unit', 'u.nama_unit', 'u.kode_unit', 'u.parent_id', 'u.level', DB::raw('COALESCE(pc.jml_pegawai, 0) as jml_pegawai')]);

        if ($search) {
            $query->where('u.nama_unit', 'ilike', "%{$search}%");
        }
        if ($level) {
            $query->where('u.level', $level);
        }

        $instansi = $query->orderBy('u.nama_unit')->paginate(30);

        $levels = DB::table('sync_ref_unit')
            ->whereNotNull('level')
            ->distinct()
            ->orderBy('level')
            ->pluck('level');

        $totalUnit = DB::table('sync_ref_unit')->count();
        $totalPegawai = DB::table('sync_peg_pegawai')->count();

        return view('master.instansi', compact('instansi', 'search', 'level', 'levels', 'totalUnit', 'totalPegawai'));
    }

    public function pegawai(Request $request)
    {
        $search = $request->get('search');
        $unitFilter = $request->get('unit');

        $query = DB::table('sync_peg_pegawai as p')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->select(['p.id_pegawai', 'p.nip', 'p.nama', 'p.status', 'p.bebas_lokasi', 'u.nama_unit']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('p.nama', 'ilike', "%{$search}%")
                  ->orWhere('p.nip', 'like', "%{$search}%");
            });
        }
        if ($unitFilter) {
            $query->where('p.id_unit', $unitFilter);
        }

        $pegawai = $query->orderBy('p.nama')->paginate(30);

        $units = DB::table('sync_ref_unit')
            ->whereNotNull('nama_unit')
            ->orderBy('nama_unit')
            ->get(['id_unit', 'nama_unit']);

        $totalPegawai = DB::table('sync_peg_pegawai')->count();
        $bebasLokasi = DB::table('sync_peg_pegawai')->where('bebas_lokasi', true)->count();

        return view('master.pegawai', compact('pegawai', 'search', 'unitFilter', 'units', 'totalPegawai', 'bebasLokasi'));
    }

    public function geofence(Request $request)
    {
        // Lokasi dari SIKARA
        $lokasiSikara = DB::table('sync_ref_lokasi_unit as l')
            ->select([
                'l.id_lokasi', 'l.nama_lokasi', 'l.latitude', 'l.longitude', 'l.radius', 'l.aktif',
                DB::raw('(SELECT COUNT(*) FROM sync_ref_bantu_unit WHERE id_lokasi = l.id_lokasi) as jml_unit'),
            ])
            ->orderBy('l.nama_lokasi')
            ->paginate(30, ['*'], 'sikara_page');

        // Ambil nama unit per lokasi untuk tooltip
        $unitPerLokasi = DB::table('sync_ref_bantu_unit as b')
            ->join('sync_ref_unit as u', 'b.id_unit', '=', 'u.id_unit')
            ->whereIn('b.id_lokasi', $lokasiSikara->pluck('id_lokasi'))
            ->select(['b.id_lokasi', 'u.nama_unit'])
            ->orderBy('u.nama_unit')
            ->get()
            ->groupBy('id_lokasi');

        // Zona geofence PANDORA (aturan hari/jam)
        $zonaPandora = DB::table('geofence_zones as z')
            ->leftJoin(DB::raw('(SELECT geofence_zone_id, COUNT(*) as jml_rules FROM geofence_rules GROUP BY geofence_zone_id) as rc'), 'z.id', '=', 'rc.geofence_zone_id')
            ->select(['z.*', DB::raw('COALESCE(rc.jml_rules, 0) as jml_rules')])
            ->orderBy('z.nama_zona')
            ->get();

        $totalLokasi = DB::table('sync_ref_lokasi_unit')->count();
        $totalAktif = DB::table('sync_ref_lokasi_unit')->where('aktif', true)->count();

        return view('master.geofence', compact('lokasiSikara', 'zonaPandora', 'totalLokasi', 'totalAktif', 'unitPerLokasi'));
    }
}
