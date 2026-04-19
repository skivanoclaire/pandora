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

    public function storeZone(Request $request)
    {
        $request->validate([
            'nama_zona' => 'required|string|max:255',
            'lat_center' => 'required|numeric',
            'long_center' => 'required|numeric',
            'radius_meter' => 'required|integer|min:10|max:10000',
        ]);

        $zone = DB::table('geofence_zones')->insertGetId([
            'nama_zona' => $request->nama_zona,
            'lat_center' => $request->lat_center,
            'long_center' => $request->long_center,
            'radius_meter' => $request->radius_meter,
            'aktif' => true,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Update PostGIS polygon
        DB::statement("UPDATE geofence_zones SET polygon = ST_Buffer(
            ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography, ?
        )::geometry WHERE id = ?", [$request->long_center, $request->lat_center, $request->radius_meter, $zone]);

        \App\Models\Integrity\AuditTrail::catat(auth()->id(), 'tambah_geofence', 'geofence_zones', $zone, ['nama' => $request->nama_zona]);

        return back()->with('success', "Zona '{$request->nama_zona}' berhasil ditambahkan.");
    }

    public function destroyZone(int $id)
    {
        $zone = DB::table('geofence_zones')->where('id', $id)->first();
        abort_unless($zone, 404);

        DB::table('geofence_rules')->where('geofence_zone_id', $id)->delete();
        DB::table('geofence_zones')->where('id', $id)->delete();

        \App\Models\Integrity\AuditTrail::catat(auth()->id(), 'hapus_geofence', 'geofence_zones', $id, ['nama' => $zone->nama_zona]);

        return back()->with('success', "Zona '{$zone->nama_zona}' berhasil dihapus.");
    }

    public function whitelist()
    {
        $whitelists = DB::table('whitelist_pegawai as w')
            ->join('sync_peg_pegawai as p', 'w.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->select(['w.*', 'p.nama', 'p.nip', 'u.nama_unit'])
            ->orderByDesc('w.created_at')
            ->paginate(30);

        $pegawaiList = DB::table('sync_peg_pegawai')->orderBy('nama')->get(['id_pegawai', 'nama', 'nip']);

        return view('master.whitelist', compact('whitelists', 'pegawaiList'));
    }

    public function storeWhitelist(Request $request)
    {
        $request->validate([
            'id_pegawai' => 'required|integer',
            'jenis_whitelist' => 'required|string|max:100',
            'alasan' => 'required|string',
            'berlaku_mulai' => 'required|date',
            'berlaku_sampai' => 'nullable|date|after_or_equal:berlaku_mulai',
        ]);

        $id = DB::table('whitelist_pegawai')->insertGetId([
            'id_pegawai' => $request->id_pegawai,
            'jenis_whitelist' => $request->jenis_whitelist,
            'alasan' => $request->alasan,
            'berlaku_mulai' => $request->berlaku_mulai,
            'berlaku_sampai' => $request->berlaku_sampai,
            'created_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \App\Models\Integrity\AuditTrail::catat(auth()->id(), 'tambah_whitelist', 'whitelist_pegawai', $id,
            ['id_pegawai' => $request->id_pegawai, 'jenis' => $request->jenis_whitelist]);

        return back()->with('success', 'Whitelist berhasil ditambahkan.');
    }

    public function destroyWhitelist(int $id)
    {
        $wl = DB::table('whitelist_pegawai')->where('id', $id)->first();
        abort_unless($wl, 404);

        DB::table('whitelist_pegawai')->where('id', $id)->delete();

        \App\Models\Integrity\AuditTrail::catat(auth()->id(), 'hapus_whitelist', 'whitelist_pegawai', $id);

        return back()->with('success', 'Whitelist berhasil dihapus.');
    }
}
