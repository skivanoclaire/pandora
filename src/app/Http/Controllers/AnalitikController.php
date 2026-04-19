<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalitikController extends Controller
{
    /**
     * SQL CASE untuk mengkategorikan jenis_ijin dari present_ijin.
     */
    private const IJIN_KATEGORI_SQL = "CASE
        WHEN jenis_ijin IN ('45','22','4') THEN 'Dinas Luar'
        WHEN jenis_ijin = '47' THEN 'Dinas Dalam'
        WHEN jenis_ijin IN ('35','34','30','36','37','38','40','41','42','50') THEN 'Cuti'
        WHEN jenis_ijin IN ('39','2','11','17','18','19','43') THEN 'Sakit'
        WHEN jenis_ijin IN ('1','10') THEN 'Izin'
        WHEN jenis_ijin IN ('48','6','24') THEN 'Dispensasi'
        WHEN jenis_ijin IN ('46','5','23') THEN 'Diklat'
        ELSE 'Lainnya'
    END";

    /**
     * Query jumlah pegawai per kategori ijin untuk satu tanggal.
     */
    private function getIjinPerTanggal(string $tanggal): object
    {
        $result = DB::table('sync_present_ijin')
            ->whereRaw("? BETWEEN tanggal_mulai AND tanggal_selesai", [$tanggal])
            ->selectRaw("
                COUNT(DISTINCT CASE WHEN jenis_ijin IN ('45','22','4') THEN id_pegawai END) as dinas_luar,
                COUNT(DISTINCT CASE WHEN jenis_ijin = '47' THEN id_pegawai END) as dinas_dalam,
                COUNT(DISTINCT CASE WHEN jenis_ijin IN ('35','34','30','36','37','38','40','41','42','50') THEN id_pegawai END) as cuti,
                COUNT(DISTINCT CASE WHEN jenis_ijin IN ('39','2','11','17','18','19','43') THEN id_pegawai END) as sakit,
                COUNT(DISTINCT CASE WHEN jenis_ijin IN ('1','10') THEN id_pegawai END) as izin,
                COUNT(DISTINCT CASE WHEN jenis_ijin IN ('48','6','24') THEN id_pegawai END) as dispensasi,
                COUNT(DISTINCT CASE WHEN jenis_ijin IN ('46','5','23') THEN id_pegawai END) as diklat,
                COUNT(DISTINCT id_pegawai) as total_ijin
            ")
            ->first();

        return $result;
    }

    /**
     * Get ijin info untuk pegawai tertentu pada tanggal tertentu.
     */
    private function getIjinPegawai(int $idPegawai, string $tanggal)
    {
        return DB::table('sync_present_ijin')
            ->whereRaw("? BETWEEN tanggal_mulai AND tanggal_selesai", [$tanggal])
            ->where('id_pegawai', $idPegawai)
            ->selectRaw("jenis_ijin, tanggal_mulai, tanggal_selesai, keterangan, " . self::IJIN_KATEGORI_SQL . " as kategori")
            ->first();
    }

    /**
     * SQL fragment untuk menghitung status kehadiran.
     * Kolom tw/mkttw/tk di SIKARA kosong sejak Nov 2020.
     * Fallback: hitung dari jam_masuk vs jadwal kerja (07:30 default).
     */
    private function statusSql(): array
    {
        // Ambil jam masuk jadwal aktif saat ini
        $jadwal = DB::table('sync_present_group')
            ->whereRaw('berlaku <= CURRENT_DATE AND berakhir >= CURRENT_DATE')
            ->first();
        $jamMasuk = $jadwal->sen_awal ?? '07:30:00';

        return [
            'hadir' => "SUM(CASE
                WHEN COALESCE(tw, 0) = 1 OR COALESCE(mkttw, 0) = 1 THEN 1
                WHEN jam_masuk IS NOT NULL THEN 1
                ELSE 0
            END)",
            'terlambat' => "SUM(CASE
                WHEN COALESCE(mkttw, 0) = 1 THEN 1
                WHEN jam_masuk IS NOT NULL AND jam_masuk > '{$jamMasuk}' THEN 1
                ELSE 0
            END)",
            'tidak_hadir' => "SUM(CASE
                WHEN COALESCE(tk, 0) = 1 THEN 1
                WHEN jam_masuk IS NULL THEN 1
                ELSE 0
            END)",
        ];
    }

    public function tren(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $days = min(max($days, 7), 365);
        $start = Carbon::today()->subDays($days - 1)->toDateString();
        $end = Carbon::today()->toDateString();

        $sql = $this->statusSql();

        $totalPegawai = DB::table('sync_peg_pegawai')->count();

        $tren = DB::table('sync_present_rekap')
            ->selectRaw("
                tanggal,
                COUNT(*) as rekap_count,
                {$sql['hadir']} as hadir,
                {$sql['terlambat']} as terlambat
            ")
            ->whereBetween('tanggal', [$start, $end])
            // Exclude Sabtu (6) dan Minggu (0)
            ->whereRaw("EXTRACT(DOW FROM tanggal) NOT IN (0, 6)")
            // Exclude hari libur nasional & cuti bersama
            ->whereNotIn('tanggal', DB::table('sync_present_libur')->pluck('tanggal'))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->map(function ($r) use ($totalPegawai) {
                $tidakHadir = $totalPegawai - (int) $r->hadir;
                $ijin = $this->getIjinPerTanggal($r->tanggal);
                $tanpaKeterangan = max(0, $tidakHadir - $ijin->total_ijin);

                return [
                    'tanggal' => $r->tanggal,
                    'label' => Carbon::parse($r->tanggal)->format('d M'),
                    'total' => $totalPegawai,
                    'hadir' => (int) $r->hadir,
                    'terlambat' => (int) $r->terlambat,
                    'tidak_hadir' => $tidakHadir,
                    'dinas_luar' => $ijin->dinas_luar + $ijin->dinas_dalam,
                    'cuti' => $ijin->cuti,
                    'sakit' => $ijin->sakit,
                    'izin_lain' => $ijin->izin + $ijin->dispensasi + $ijin->diklat,
                    'tanpa_keterangan' => $tanpaKeterangan,
                    'persen_hadir' => $totalPegawai > 0 ? round(($r->hadir / $totalPegawai) * 100, 1) : 0,
                ];
            });

        return view('analitik.tren', compact('tren', 'days'));
    }

    public function trenDetail(string $tanggal)
    {
        $jamMasuk = DB::table('sync_present_group')
            ->whereRaw('berlaku <= CURRENT_DATE AND berakhir >= CURRENT_DATE')
            ->value('sen_awal') ?? '07:30:00';

        $totalPegawai = DB::table('sync_peg_pegawai')->count();

        // Ringkasan hari itu
        $rekapStats = DB::table('sync_present_rekap')
            ->where('tanggal', $tanggal)
            ->selectRaw("
                SUM(CASE WHEN jam_masuk IS NOT NULL THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN jam_masuk IS NOT NULL AND jam_masuk > '{$jamMasuk}' THEN 1 ELSE 0 END) as terlambat
            ")->first();

        $ijinStats = $this->getIjinPerTanggal($tanggal);
        $tidakHadir = $totalPegawai - (int) ($rekapStats->hadir ?? 0);

        $summary = (object) [
            'total' => $totalPegawai,
            'hadir' => (int) ($rekapStats->hadir ?? 0),
            'terlambat' => (int) ($rekapStats->terlambat ?? 0),
            'tidak_hadir' => $tidakHadir,
            'dinas_luar' => $ijinStats->dinas_luar,
            'dinas_dalam' => $ijinStats->dinas_dalam,
            'cuti' => $ijinStats->cuti,
            'sakit' => $ijinStats->sakit,
            'izin' => $ijinStats->izin,
            'dispensasi' => $ijinStats->dispensasi,
            'diklat' => $ijinStats->diklat,
            'total_ijin' => $ijinStats->total_ijin,
            'tanpa_keterangan' => max(0, $tidakHadir - $ijinStats->total_ijin),
        ];

        // Daftar pegawai terlambat
        $terlambat = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('r.tanggal', $tanggal)
            ->whereNotNull('r.jam_masuk')
            ->where('r.jam_masuk', '>', $jamMasuk)
            ->select([
                'p.nama', 'p.nip', 'u.nama_unit', 'u.id_unit',
                'r.jam_masuk', 'r.jam_pulang',
                'r.nama_lokasi_berangkat',
                DB::raw("EXTRACT(EPOCH FROM (r.jam_masuk::time - '{$jamMasuk}'::time)) / 60 as menit_terlambat"),
            ])
            ->orderByDesc('menit_terlambat')
            ->limit(100)
            ->get();

        // Ranking instansi paling banyak terlambat
        $rankInstansi = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->join('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('r.tanggal', $tanggal)
            ->whereNotNull('r.jam_masuk')
            ->where('r.jam_masuk', '>', $jamMasuk)
            ->selectRaw("
                u.nama_unit,
                COUNT(*) as jumlah_terlambat,
                ROUND(AVG(EXTRACT(EPOCH FROM (r.jam_masuk::time - '{$jamMasuk}'::time)) / 60)) as rata_menit,
                (SELECT COUNT(*) FROM sync_present_rekap r2
                 JOIN sync_peg_pegawai p2 ON r2.id_pegawai = p2.id_pegawai
                 WHERE r2.tanggal = '{$tanggal}' AND p2.id_unit = u.id_unit) as total_pegawai
            ")
            ->groupBy('u.nama_unit', 'u.id_unit')
            ->orderByDesc('jumlah_terlambat')
            ->limit(20)
            ->get()
            ->map(function ($r) {
                $r->persen_terlambat = $r->total_pegawai > 0
                    ? round(($r->jumlah_terlambat / $r->total_pegawai) * 100, 1)
                    : 0;
                return $r;
            });

        // Daftar yang tidak hadir (termasuk yang sama sekali tidak ada record)
        $tidakHadir = DB::table('sync_peg_pegawai as p')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereNotIn('p.id_pegawai', function ($q) use ($tanggal) {
                $q->select('id_pegawai')
                  ->from('sync_present_rekap')
                  ->where('tanggal', $tanggal)
                  ->whereNotNull('jam_masuk');
            })
            ->select(['p.nama', 'p.nip', 'u.nama_unit'])
            ->orderBy('u.nama_unit')
            ->orderBy('p.nama')
            ->limit(100)
            ->get();

        return view('analitik.tren-detail', compact(
            'tanggal', 'jamMasuk', 'summary', 'terlambat', 'rankInstansi', 'tidakHadir',
        ));
    }

    public function trenDinas(string $tanggal)
    {
        $kategoriSql = self::IJIN_KATEGORI_SQL;

        // Daftar pegawai DL/DD pada tanggal ini
        $dinas = DB::table('sync_present_ijin as ij')
            ->join('sync_peg_pegawai as p', 'ij.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereRaw("? BETWEEN ij.tanggal_mulai AND ij.tanggal_selesai", [$tanggal])
            ->whereIn('ij.jenis_ijin', ['4', '22', '45', '47']) // DL + DD
            ->selectRaw("p.nama, p.nip, u.nama_unit, ij.jenis_ijin, ij.tanggal_mulai, ij.tanggal_selesai, ij.keterangan, {$kategoriSql} as kategori")
            ->orderByRaw("{$kategoriSql}")
            ->orderBy('u.nama_unit')
            ->orderBy('p.nama')
            ->get();

        // Ringkasan per instansi
        $perInstansi = DB::table('sync_present_ijin as ij')
            ->join('sync_peg_pegawai as p', 'ij.id_pegawai', '=', 'p.id_pegawai')
            ->join('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereRaw("? BETWEEN ij.tanggal_mulai AND ij.tanggal_selesai", [$tanggal])
            ->whereIn('ij.jenis_ijin', ['4', '22', '45', '47'])
            ->selectRaw("u.nama_unit, COUNT(*) as jumlah,
                SUM(CASE WHEN ij.jenis_ijin IN ('45','22','4') THEN 1 ELSE 0 END) as dl,
                SUM(CASE WHEN ij.jenis_ijin = '47' THEN 1 ELSE 0 END) as dd")
            ->groupBy('u.nama_unit')
            ->orderByDesc('jumlah')
            ->limit(20)
            ->get();

        $totalDL = $dinas->where('kategori', 'Dinas Luar')->count();
        $totalDD = $dinas->where('kategori', 'Dinas Dalam')->count();

        return view('analitik.tren-dinas', compact('tanggal', 'dinas', 'perInstansi', 'totalDL', 'totalDD'));
    }

    public function trenTanpaKeterangan(string $tanggal)
    {
        // Pegawai yang hadir (punya jam_masuk di rekap)
        $hadirIds = DB::table('sync_present_rekap')
            ->where('tanggal', $tanggal)
            ->whereNotNull('jam_masuk')
            ->pluck('id_pegawai');

        // Pegawai yang punya ijin pada tanggal itu
        $ijinIds = DB::table('sync_present_ijin')
            ->whereRaw("? BETWEEN tanggal_mulai AND tanggal_selesai", [$tanggal])
            ->pluck('id_pegawai')
            ->unique();

        // Tanpa keterangan = tidak hadir DAN tidak ada ijin
        $excludeIds = $hadirIds->merge($ijinIds)->unique();

        $pegawai = DB::table('sync_peg_pegawai as p')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereNotIn('p.id_pegawai', $excludeIds)
            ->select(['p.nama', 'p.nip', 'u.nama_unit'])
            ->orderBy('u.nama_unit')
            ->orderBy('p.nama')
            ->get();

        // Per instansi
        $perInstansi = DB::table('sync_peg_pegawai as p')
            ->join('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereNotIn('p.id_pegawai', $excludeIds)
            ->selectRaw('u.nama_unit, COUNT(*) as jumlah')
            ->groupBy('u.nama_unit')
            ->orderByDesc('jumlah')
            ->limit(20)
            ->get();

        return view('analitik.tren-tanpa-keterangan', compact('tanggal', 'pegawai', 'perInstansi'));
    }

    public function trenIjin(string $tanggal, string $kategori)
    {
        $kategoriMap = [
            'cuti' => ['kode' => ['35','34','30','36','37','38','40','41','42','50'], 'label' => 'Cuti'],
            'sakit' => ['kode' => ['39','2','11','17','18','19','43'], 'label' => 'Sakit'],
            'izin' => ['kode' => ['1','10'], 'label' => 'Izin'],
            'dispensasi' => ['kode' => ['48','6','24'], 'label' => 'Dispensasi'],
            'diklat' => ['kode' => ['46','5','23'], 'label' => 'Diklat'],
        ];

        abort_unless(isset($kategoriMap[$kategori]), 404);

        $config = $kategoriMap[$kategori];
        $kategoriSql = self::IJIN_KATEGORI_SQL;

        $pegawai = DB::table('sync_present_ijin as ij')
            ->join('sync_peg_pegawai as p', 'ij.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereRaw("? BETWEEN ij.tanggal_mulai AND ij.tanggal_selesai", [$tanggal])
            ->whereIn('ij.jenis_ijin', $config['kode'])
            ->selectRaw("p.nama, p.nip, u.nama_unit, ij.jenis_ijin, ij.tanggal_mulai, ij.tanggal_selesai, ij.keterangan, {$kategoriSql} as kategori")
            ->orderBy('u.nama_unit')
            ->orderBy('p.nama')
            ->get();

        $perInstansi = DB::table('sync_present_ijin as ij')
            ->join('sync_peg_pegawai as p', 'ij.id_pegawai', '=', 'p.id_pegawai')
            ->join('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereRaw("? BETWEEN ij.tanggal_mulai AND ij.tanggal_selesai", [$tanggal])
            ->whereIn('ij.jenis_ijin', $config['kode'])
            ->selectRaw("u.nama_unit, COUNT(*) as jumlah")
            ->groupBy('u.nama_unit')
            ->orderByDesc('jumlah')
            ->limit(20)
            ->get();

        $label = $config['label'];

        return view('analitik.tren-ijin', compact('tanggal', 'kategori', 'label', 'pegawai', 'perInstansi'));
    }

    public function anomali(Request $request)
    {
        $tingkat = $request->get('tingkat');
        $jenis = $request->get('jenis');
        $status = $request->get('status', 'belum_direview');
        $sort = $request->get('sort', 'confidence');
        $dir = $request->get('dir', 'desc');

        // Whitelist sort columns
        $allowedSorts = ['detected_at', 'confidence', 'tingkat', 'tanggal'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'detected_at';
        }
        $dir = $dir === 'asc' ? 'asc' : 'desc';

        $query = DB::table('anomaly_flags as a')
            ->join('sync_peg_pegawai as p', 'a.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit');

        if ($tingkat) {
            $query->where('a.tingkat', $tingkat);
        }
        if ($jenis) {
            $query->where('a.jenis_anomali', $jenis);
        }
        if ($status) {
            $query->where('a.status_review', $status);
        }

        $anomalies = $query
            ->orderBy('a.' . $sort, $dir)
            ->select([
                'a.id', 'a.tanggal', 'a.jenis_anomali', 'a.tingkat', 'a.confidence',
                'a.metode_deteksi', 'a.metadata', 'a.status_review', 'a.detected_at',
                'p.nama', 'p.nip', 'u.nama_unit',
            ])
            ->paginate(30);

        // Statistik ringkasan
        $statsByTingkat = DB::table('anomaly_flags')
            ->where('status_review', 'belum_direview')
            ->selectRaw('tingkat, COUNT(*) as jumlah')
            ->groupBy('tingkat')
            ->orderBy('tingkat')
            ->pluck('jumlah', 'tingkat');

        $statsByJenis = DB::table('anomaly_flags')
            ->where('status_review', 'belum_direview')
            ->selectRaw('jenis_anomali, COUNT(*) as jumlah')
            ->groupBy('jenis_anomali')
            ->orderByDesc('jumlah')
            ->pluck('jumlah', 'jenis_anomali');

        return view('analitik.anomali', compact(
            'anomalies', 'tingkat', 'jenis', 'status', 'sort', 'dir', 'statsByTingkat', 'statsByJenis',
        ));
    }

    public function detailAnomali(int $id, \App\Services\GeocodingService $geocoding)
    {
        $anomaly = DB::table('anomaly_flags as a')
            ->join('sync_peg_pegawai as p', 'a.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('a.id', $id)
            ->select([
                'a.*', 'p.nama', 'p.nip', 'p.id_unit', 'p.bebas_lokasi',
                'u.nama_unit',
            ])
            ->first();

        abort_unless($anomaly, 404);

        $meta = json_decode($anomaly->metadata, true) ?? [];

        // Data rekap hari itu
        $rekap = DB::table('sync_present_rekap')
            ->where('id_pegawai', $anomaly->id_pegawai)
            ->where('tanggal', $anomaly->tanggal)
            ->first();

        // Feature engineering hari itu
        $features = DB::table('features_kehadiran_harian')
            ->where('id_pegawai', $anomaly->id_pegawai)
            ->where('tanggal', $anomaly->tanggal)
            ->first();

        // Geofence terdekat
        $geofenceInfo = null;
        if ($rekap && $rekap->lat_berangkat) {
            $geofenceInfo = DB::table('geofence_zones')
                ->whereNotNull('lat_center')
                ->where('aktif', true)
                ->selectRaw("nama_zona, radius_meter,
                    ST_Distance(
                        ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography,
                        ST_SetSRID(ST_MakePoint(long_center, lat_center), 4326)::geography
                    ) as jarak_meter", [$rekap->long_berangkat, $rekap->lat_berangkat])
                ->orderBy('jarak_meter')
                ->limit(3)
                ->get();
        }

        // Riwayat anomali pegawai ini (30 hari terakhir)
        $riwayat = DB::table('anomaly_flags')
            ->where('id_pegawai', $anomaly->id_pegawai)
            ->where('tanggal', '>=', Carbon::parse($anomaly->tanggal)->subDays(30)->toDateString())
            ->where('tanggal', '<=', $anomaly->tanggal)
            ->orderByDesc('tanggal')
            ->limit(10)
            ->get();

        // Pola kehadiran 14 hari sebelum anomali (untuk pembanding)
        $polaKehadiran = DB::table('sync_present_rekap')
            ->where('id_pegawai', $anomaly->id_pegawai)
            ->where('tanggal', '>=', Carbon::parse($anomaly->tanggal)->subDays(13)->toDateString())
            ->where('tanggal', '<=', $anomaly->tanggal)
            ->orderBy('tanggal')
            ->get();

        // Reverse geocode setiap hari
        foreach ($polaKehadiran as $pk) {
            $pk->geo_berangkat = null;
            $pk->diluar_kaltara = false;
            if ($pk->lat_berangkat) {
                $pk->geo_berangkat = $geocoding->reverseGeocode((float) $pk->lat_berangkat, (float) $pk->long_berangkat);
                $pk->diluar_kaltara = !$geocoding->isInKaltara((float) $pk->lat_berangkat, (float) $pk->long_berangkat);
            }
        }

        // Cek ijin/cuti/DL pegawai pada tanggal anomali
        $ijinPegawai = $this->getIjinPegawai($anomaly->id_pegawai, $anomaly->tanggal);

        // Reverse geocode lokasi check-in dan check-out
        $geoBerangkat = null;
        $geoPulang = null;
        $lokasiAlert = null;

        if ($rekap && $rekap->lat_berangkat) {
            $geoBerangkat = $geocoding->reverseGeocode((float) $rekap->lat_berangkat, (float) $rekap->long_berangkat);
            if (!$geocoding->isInKaltara((float) $rekap->lat_berangkat, (float) $rekap->long_berangkat)) {
                $lokasiAlert = 'berangkat';
            }
        }
        if ($rekap && $rekap->lat_pulang) {
            $geoPulang = $geocoding->reverseGeocode((float) $rekap->lat_pulang, (float) $rekap->long_pulang);
            if (!$geocoding->isInKaltara((float) $rekap->lat_pulang, (float) $rekap->long_pulang)) {
                $lokasiAlert = $lokasiAlert ? 'keduanya' : 'pulang';
            }
        }

        // Generate narasi
        $narasi = $this->generateNarasiAnomali($anomaly, $meta, $rekap, $features, $geofenceInfo, $riwayat);

        // Tambah narasi lokasi jika di luar Kaltara
        if ($lokasiAlert && $geoBerangkat) {
            $kotaBerangkat = $geoBerangkat['display'] ?? 'lokasi tidak dikenal';
            $kotaPulang = $geoPulang['display'] ?? null;

            if ($lokasiAlert === 'berangkat') {
                $narasi['lokasi_alert'] = "Koordinat check-in menunjukkan lokasi di **{$kotaBerangkat}** — di luar wilayah Kalimantan Utara. Pegawai ini terdaftar di OPD Kaltara tetapi melakukan absensi dari kota lain.";
            } elseif ($lokasiAlert === 'pulang') {
                $narasi['lokasi_alert'] = "Koordinat check-out menunjukkan lokasi di **{$kotaPulang}** — di luar wilayah Kalimantan Utara.";
            } else {
                $narasi['lokasi_alert'] = "Check-in dari **{$kotaBerangkat}** dan check-out dari **" . ($kotaPulang ?? $kotaBerangkat) . "** — keduanya di luar wilayah Kalimantan Utara.";
            }
        }

        // Tambah narasi ijin jika ada
        if ($ijinPegawai) {
            $durasi = '';
            if ($ijinPegawai->tanggal_mulai && $ijinPegawai->tanggal_selesai) {
                $durasi = ' (' . Carbon::parse($ijinPegawai->tanggal_mulai)->format('d M') . ' s/d ' . Carbon::parse($ijinPegawai->tanggal_selesai)->format('d M') . ')';
            }
            $narasi['ijin'] = "Menurut data ijin SIKARA, pegawai ini berstatus **{$ijinPegawai->kategori}**{$durasi}.";
            if ($ijinPegawai->keterangan) {
                $narasi['ijin'] .= " Keterangan: *\"{$ijinPegawai->keterangan}\"*";
            }
        }

        return view('analitik.anomali-detail', compact(
            'anomaly', 'meta', 'rekap', 'features', 'geofenceInfo', 'riwayat', 'polaKehadiran', 'narasi',
            'geoBerangkat', 'geoPulang', 'lokasiAlert', 'ijinPegawai',
        ));
    }

    private function generateNarasiAnomali($anomaly, array $meta, $rekap, $features, $geofenceInfo, $riwayat): array
    {
        $narasi = [];
        $nama = $anomaly->nama;
        $conf = round($anomaly->confidence * 100);
        $tanggal = Carbon::parse($anomaly->tanggal)->translatedFormat('l, d F Y');

        // 1. Ringkasan utama
        $tingkatLabel = match ((int) $anomaly->tingkat) {
            1 => 'ketidakmungkinan fisik (Tingkat 1)',
            2 => 'pelanggaran aturan formal (Tingkat 2)',
            3 => 'anomali statistik (Tingkat 3)',
            default => 'kandidat false positive (Tingkat 4)',
        };
        $narasi['ringkasan'] = "Pada {$tanggal}, sistem mendeteksi {$tingkatLabel} pada data kehadiran **{$nama}** dengan tingkat keyakinan **{$conf}%**.";

        // 2. Implikasi confidence
        if ($conf >= 80) {
            $narasi['confidence'] = "Tingkat keyakinan {$conf}% tergolong **sangat tinggi**. Ini berarti model sangat yakin bahwa pola data ini tidak wajar. Kemungkinan besar ini bukan kebetulan dan perlu ditindaklanjuti.";
        } elseif ($conf >= 60) {
            $narasi['confidence'] = "Tingkat keyakinan {$conf}% tergolong **cukup tinggi**. Sistem cukup yakin ada pola tidak biasa, namun masih ada kemungkinan ~" . (100 - $conf) . "% bahwa ini disebabkan faktor legitimate (misalnya tugas lapangan mendadak, gangguan GPS perangkat).";
        } elseif ($conf >= 40) {
            $narasi['confidence'] = "Tingkat keyakinan {$conf}% tergolong **sedang**. Pola ini menyimpang dari rata-rata tapi belum cukup kuat untuk disimpulkan sebagai kecurangan. Perlu konteks tambahan dari atasan langsung pegawai.";
        } else {
            $narasi['confidence'] = "Tingkat keyakinan {$conf}% tergolong **rendah**. Penyimpangan yang terdeteksi hanya sedikit di atas ambang batas. Kemungkinan besar ini adalah variasi normal atau false positive.";
        }

        // 3. Penjelasan spesifik per jenis
        $jenisNarasi = match ($anomaly->jenis_anomali) {
            'fake_gps' => $this->narasiFakeGps($meta, $rekap),
            'velocity_outlier' => $this->narasiVelocity($meta, $features),
            'geofence_violation' => $this->narasiGeofence($meta, $geofenceInfo, $rekap),
            'temporal_outlier' => $this->narasiTemporal($meta, $features),
            'combination' => $this->narasiCombination($anomaly, $meta, $features, $geofenceInfo),
            default => 'Detail spesifik untuk jenis anomali ini belum tersedia.',
        };
        $narasi['jenis'] = $jenisNarasi;

        // 4. Konteks kehadiran hari itu
        if ($rekap) {
            $jamMasukJadwal = DB::table('sync_present_group')
                ->whereRaw('berlaku <= CURRENT_DATE AND berakhir >= CURRENT_DATE')
                ->value('sen_awal') ?? '07:30:00';

            $statusParts = [];
            // Cek kolom SIKARA dulu, fallback ke jam_masuk
            if ($rekap->dl == 1) $statusParts[] = 'Dinas Luar';
            if ($rekap->i == 1) $statusParts[] = 'Izin';
            if ($rekap->s == 1) $statusParts[] = 'Sakit';
            if ($rekap->c == 1) $statusParts[] = 'Cuti';
            if ($rekap->dsp == 1) $statusParts[] = 'Dispensasi';

            if ($rekap->tw == 1) {
                $statusParts[] = 'Tepat Waktu';
            } elseif ($rekap->mkttw == 1) {
                $statusParts[] = 'Terlambat';
            } elseif ($rekap->tk == 1) {
                $statusParts[] = 'Tanpa Kehadiran';
            } elseif (empty($statusParts)) {
                // Fallback: hitung dari jam_masuk
                if ($rekap->jam_masuk) {
                    $statusParts[] = $rekap->jam_masuk <= $jamMasukJadwal ? 'Tepat Waktu' : 'Terlambat (' . Carbon::parse($rekap->jam_masuk)->format('H:i') . ')';
                } else {
                    $statusParts[] = 'Tidak Hadir (tidak ada check-in)';
                }
            }
            $statusStr = implode(', ', $statusParts);

            $masuk = $rekap->jam_masuk ? Carbon::parse($rekap->jam_masuk)->format('H:i') : '-';
            $pulang = $rekap->jam_pulang ? Carbon::parse($rekap->jam_pulang)->format('H:i') : '-';

            $narasi['kehadiran'] = "Status SIKARA: **{$statusStr}**. Jam masuk: {$masuk}, jam pulang: {$pulang}.";
            if ($rekap->nama_lokasi_berangkat) {
                $narasi['kehadiran'] .= " Lokasi check-in: {$rekap->nama_lokasi_berangkat}.";
            }
        }

        // 5. Pola historis
        $totalAnomali30 = $riwayat->count();
        if ($totalAnomali30 > 1) {
            $narasi['pola'] = "Dalam 30 hari terakhir, {$nama} memiliki **{$totalAnomali30} anomali** terdeteksi. Pola berulang ini perlu perhatian lebih serius.";
        } else {
            $narasi['pola'] = "Ini adalah anomali pertama yang terdeteksi untuk {$nama} dalam 30 hari terakhir. Kemungkinan insiden terisolasi.";
        }

        // 6. Rekomendasi
        if ($anomaly->tingkat == 1 && $conf >= 70) {
            $narasi['rekomendasi'] = "**Rekomendasi:** Lakukan konfirmasi langsung ke pegawai atau atasan. Anomali Tingkat 1 dengan keyakinan tinggi mengindikasikan ketidakmungkinan fisik yang sulit dijelaskan oleh faktor teknis.";
        } elseif ($anomaly->tingkat == 3) {
            $narasi['rekomendasi'] = "**Rekomendasi:** Tandai untuk monitoring lanjutan. Anomali Tingkat 3 (statistik) perlu divalidasi konteks oleh Pimpinan OPD sebelum disimpulkan.";
        } else {
            $narasi['rekomendasi'] = "**Rekomendasi:** Review oleh admin DKISP untuk menentukan apakah anomali ini valid atau false positive berdasarkan konteks operasional.";
        }

        return $narasi;
    }

    private function narasiFakeGps(array $meta, $rekap): string
    {
        $parts = [];
        $rule = $meta['rule'] ?? 'unknown';

        if ($rule === 'koordinat_berulang_identik') {
            $jumlahHari = $meta['jumlah_hari'] ?? 0;
            $window = $meta['window_hari'] ?? 7;
            $tanggalList = $meta['tanggal_kemunculan'] ?? [];
            $lat = round($meta['lat'] ?? 0, 4);
            $lon = round($meta['lon'] ?? 0, 4);

            $parts[] = "Sistem mendeteksi koordinat GPS **identik persis** ({$lat}, {$lon}) pada **{$jumlahHari} hari berbeda** dalam {$window} hari terakhir.";
            $parts[] = "Tanggal kemunculan: " . implode(', ', $tanggalList) . ".";
            $parts[] = "Koordinat GPS yang benar-benar sama hingga desimal keenam pada hari berbeda sangat tidak wajar secara fisik — posisi GPS asli selalu berfluktuasi beberapa meter. Pola ini konsisten dengan penggunaan aplikasi pemalsuan lokasi (Fake GPS).";
        } else {
            $parts[] = "Terdeteksi pola yang konsisten dengan penggunaan Fake GPS berdasarkan rule: *{$rule}*.";
        }

        return implode(' ', $parts);
    }

    private function narasiVelocity(array $meta, $features): string
    {
        $velocity = $meta['features']['velocity_berangkat_pulang'] ?? ($features->velocity_berangkat_pulang ?? null);
        $velocityKemarin = $meta['features']['velocity_vs_kemarin'] ?? ($features->velocity_vs_kemarin ?? null);

        $parts = [];
        if ($velocity !== null) {
            $v = round($velocity, 1);
            $parts[] = "Kecepatan perpindahan antara lokasi check-in dan check-out: **{$v} km/jam**.";
            if ($v > 300) {
                $parts[] = "Kecepatan > 300 km/jam tidak mungkin dicapai dengan transportasi darat manapun di Kalimantan Utara. Ini mengindikasikan lokasi GPS yang tidak akurat atau dipalsukan.";
            } elseif ($v > 100) {
                $parts[] = "Kecepatan ini sangat tinggi untuk wilayah Kaltara dan perlu penjelasan (misalnya penerbangan antar kota).";
            }
        }
        return !empty($parts) ? implode(' ', $parts) : 'Kecepatan perpindahan antar sesi absensi melebihi ambang batas wajar.';
    }

    private function narasiGeofence(array $meta, $geofenceInfo, $rekap): string
    {
        $parts = [];
        if ($geofenceInfo && $geofenceInfo->count() > 0) {
            $nearest = $geofenceInfo->first();
            $jarak = round($nearest->jarak_meter);
            $parts[] = "Lokasi check-in berjarak **{$jarak} meter** dari zona geofence terdekat ({$nearest->nama_zona}, radius {$nearest->radius_meter}m).";
            if ($jarak > 1000) {
                $parts[] = "Jarak > 1 km dari zona manapun — pegawai melakukan absensi jauh di luar area yang diizinkan.";
            } else {
                $parts[] = "Pegawai berada di luar radius zona yang ditetapkan.";
            }
        }
        return !empty($parts) ? implode(' ', $parts) : 'Lokasi absensi berada di luar zona geofence yang ditetapkan.';
    }

    private function narasiTemporal(array $meta, $features): string
    {
        $devMasuk = $meta['features']['deviasi_masuk_vs_jadwal_ekspektasi'] ?? ($features->deviasi_masuk_vs_jadwal_ekspektasi ?? null);
        $devMedian = $meta['features']['deviasi_waktu_masuk_vs_median_personal'] ?? ($features->deviasi_waktu_masuk_vs_median_personal ?? null);

        $parts = [];
        if ($devMasuk !== null) {
            $d = round(abs($devMasuk));
            $direction = $devMasuk < 0 ? 'lebih awal' : 'lebih lambat';
            $parts[] = "Waktu masuk **{$d} menit {$direction}** dari jadwal yang ditetapkan.";
        }
        if ($devMedian !== null) {
            $d = round(abs($devMedian));
            $parts[] = "Menyimpang **{$d} menit** dari pola kebiasaan pribadi pegawai ini.";
        }
        return !empty($parts) ? implode(' ', $parts) : 'Pola waktu absensi menyimpang signifikan dari kebiasaan.';
    }

    private function narasiCombination($anomaly, array $meta, $features, $geofenceInfo): string
    {
        $metode = $anomaly->metode_deteksi;
        $parts = [];

        if ($metode === 'isolation_forest') {
            $ifScore = $meta['if_score'] ?? null;
            $parts[] = "Model Isolation Forest mendeteksi data ini sebagai **outlier multivariate** — artinya kombinasi dari beberapa fitur (lokasi, waktu, jarak geofence) secara bersamaan tidak biasa dibandingkan pegawai lain.";

            if (isset($meta['features'])) {
                $f = $meta['features'];
                $highlights = [];
                if (isset($f['jarak_dari_geofence_berangkat']) && $f['jarak_dari_geofence_berangkat'] > 1000) {
                    $highlights[] = "jarak geofence berangkat " . number_format($f['jarak_dari_geofence_berangkat']) . "m";
                }
                if (isset($f['jarak_dari_geofence_pulang']) && $f['jarak_dari_geofence_pulang'] > 1000) {
                    $highlights[] = "jarak geofence pulang " . number_format($f['jarak_dari_geofence_pulang']) . "m";
                }
                if (isset($f['deviasi_masuk_vs_jadwal_ekspektasi'])) {
                    $highlights[] = "deviasi masuk " . round($f['deviasi_masuk_vs_jadwal_ekspektasi']) . " menit";
                }
                if (!empty($highlights)) {
                    $parts[] = "Fitur yang paling menyimpang: " . implode(', ', $highlights) . ".";
                }
            }
        } elseif ($metode === 'dbscan') {
            $parts[] = "Model DBSCAN (clustering spasial) mengidentifikasi lokasi check-in ini sebagai **noise point** — titik yang tidak masuk ke cluster manapun.";
            $parts[] = "Artinya lokasi ini terisolasi jauh dari pola lokasi check-in pegawai lain. Ini bisa berarti pegawai bertugas di lokasi yang tidak umum, atau koordinat GPS bermasalah.";
        }

        return !empty($parts) ? implode(' ', $parts) : 'Kombinasi beberapa faktor mengindikasikan pola tidak biasa.';
    }

    public function reviewAnomali(Request $request, int $id)
    {
        $request->validate([
            'status_review' => 'required|in:valid,false_positive',
            'catatan_review' => 'nullable|string|max:1000',
        ]);

        $anomaly = DB::table('anomaly_flags')->where('id', $id)->first();
        abort_unless($anomaly, 404);

        DB::table('anomaly_flags')->where('id', $id)->update([
            'status_review' => $request->status_review,
            'direview_oleh' => auth()->id(),
            'direview_pada' => now(),
            'catatan_review' => $request->catatan_review,
            'updated_at' => now(),
        ]);

        \App\Models\Integrity\AuditTrail::catat(
            auth()->id(),
            'review_anomali',
            'anomaly_flags',
            $id,
            ['status_review' => $request->status_review, 'catatan' => $request->catatan_review]
        );

        return back()->with('success', 'Anomali berhasil direview.');
    }

    public function exportAnomaliPdf(Request $request)
    {
        $tingkat = $request->get('tingkat');
        $jenis = $request->get('jenis');
        $dari = $request->get('dari', Carbon::today()->subDays(30)->toDateString());
        $sampai = $request->get('sampai', Carbon::today()->toDateString());

        $query = DB::table('anomaly_flags as a')
            ->join('sync_peg_pegawai as p', 'a.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereBetween('a.tanggal', [$dari, $sampai]);

        if ($tingkat) $query->where('a.tingkat', $tingkat);
        if ($jenis) $query->where('a.jenis_anomali', $jenis);

        $anomalies = $query
            ->orderByDesc('a.confidence')
            ->select(['a.id', 'a.tanggal', 'a.jenis_anomali', 'a.tingkat', 'a.confidence',
                      'a.metode_deteksi', 'a.status_review', 'p.nama', 'p.nip', 'u.nama_unit'])
            ->limit(500)
            ->get();

        $summary = [
            'dari' => $dari,
            'sampai' => $sampai,
            'total' => $anomalies->count(),
            'tingkat1' => $anomalies->where('tingkat', 1)->count(),
            'tingkat3' => $anomalies->where('tingkat', 3)->count(),
        ];

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.anomali-pdf', compact('anomalies', 'summary'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("laporan-anomali-{$dari}-{$sampai}.pdf");
    }

    public function clustering(Request $request, \App\Services\GeocodingService $geocoding)
    {
        $tanggalAwal = $request->get('dari', Carbon::today()->subDays(30)->toDateString());
        $tanggalAkhir = $request->get('sampai', Carbon::today()->toDateString());

        // Ambil anomali DBSCAN noise points
        $noisePoints = DB::table('anomaly_flags as a')
            ->join('sync_present_rekap as r', function ($join) {
                $join->on('a.id_pegawai', '=', 'r.id_pegawai')
                     ->on('a.tanggal', '=', 'r.tanggal');
            })
            ->join('sync_peg_pegawai as p', 'a.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('a.metode_deteksi', 'dbscan')
            ->whereBetween('a.tanggal', [$tanggalAwal, $tanggalAkhir])
            ->whereNotNull('r.lat_berangkat')
            ->select([
                'a.id', 'a.tanggal', 'r.lat_berangkat as lat', 'r.long_berangkat as lng',
                'a.confidence', 'a.metadata', 'p.nama', 'p.nip', 'u.nama_unit',
                'r.nama_lokasi_berangkat',
            ])
            ->orderByDesc('a.confidence')
            ->limit(500)
            ->get()
            ->map(function ($r) {
                $r->metadata = json_decode($r->metadata, true);
                return $r;
            });

        // IF outliers
        $ifOutliers = DB::table('anomaly_flags as a')
            ->join('sync_present_rekap as r', function ($join) {
                $join->on('a.id_pegawai', '=', 'r.id_pegawai')
                     ->on('a.tanggal', '=', 'r.tanggal');
            })
            ->join('sync_peg_pegawai as p', 'a.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('a.metode_deteksi', 'isolation_forest')
            ->whereBetween('a.tanggal', [$tanggalAwal, $tanggalAkhir])
            ->whereNotNull('r.lat_berangkat')
            ->select([
                'a.id', 'a.tanggal', 'r.lat_berangkat as lat', 'r.long_berangkat as lng',
                'a.confidence', 'a.metadata', 'p.nama', 'p.nip', 'u.nama_unit',
            ])
            ->orderByDesc('a.confidence')
            ->limit(500)
            ->get()
            ->map(function ($r) {
                $r->metadata = json_decode($r->metadata, true);
                return $r;
            });

        // Hotspot: lokasi yang muncul berulang sebagai anomali
        $hotspots = DB::table('anomaly_flags as a')
            ->join('sync_present_rekap as r', function ($join) {
                $join->on('a.id_pegawai', '=', 'r.id_pegawai')
                     ->on('a.tanggal', '=', 'r.tanggal');
            })
            ->whereBetween('a.tanggal', [$tanggalAwal, $tanggalAkhir])
            ->whereNotNull('r.nama_lokasi_berangkat')
            ->selectRaw("r.nama_lokasi_berangkat as lokasi,
                COUNT(*) as jumlah_anomali,
                COUNT(DISTINCT a.id_pegawai) as jumlah_pegawai,
                ROUND(AVG(r.lat_berangkat::numeric), 5) as avg_lat,
                ROUND(AVG(r.long_berangkat::numeric), 5) as avg_lng")
            ->groupBy('r.nama_lokasi_berangkat')
            ->havingRaw('COUNT(*) >= 3')
            ->orderByDesc('jumlah_anomali')
            ->limit(10)
            ->get();

        // Instansi dengan anomali terbanyak
        $instansiAnomali = DB::table('anomaly_flags as a')
            ->join('sync_peg_pegawai as p', 'a.id_pegawai', '=', 'p.id_pegawai')
            ->join('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereBetween('a.tanggal', [$tanggalAwal, $tanggalAkhir])
            ->whereIn('a.metode_deteksi', ['dbscan', 'isolation_forest'])
            ->selectRaw("u.nama_unit, COUNT(*) as total_anomali, COUNT(DISTINCT a.id_pegawai) as pegawai_unik")
            ->groupBy('u.nama_unit')
            ->orderByDesc('total_anomali')
            ->limit(10)
            ->get();

        // Pegawai di luar Kaltara (bounding box check)
        $diluarKaltara = DB::table('anomaly_flags as a')
            ->join('sync_present_rekap as r', function ($join) {
                $join->on('a.id_pegawai', '=', 'r.id_pegawai')
                     ->on('a.tanggal', '=', 'r.tanggal');
            })
            ->join('sync_peg_pegawai as p', 'a.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereBetween('a.tanggal', [$tanggalAwal, $tanggalAkhir])
            ->whereNotNull('r.lat_berangkat')
            ->where(function ($q) {
                $q->where('r.lat_berangkat', '<', 1.0)
                  ->orWhere('r.lat_berangkat', '>', 4.5)
                  ->orWhere('r.long_berangkat', '<', 115.0)
                  ->orWhere('r.long_berangkat', '>', 118.0);
            })
            ->select([
                'a.id', 'a.tanggal', 'p.nama', 'p.nip', 'u.nama_unit',
                'r.lat_berangkat as lat', 'r.long_berangkat as lng',
                'r.nama_lokasi_berangkat',
            ])
            ->orderByDesc('a.tanggal')
            ->limit(20)
            ->get();

        // Reverse geocode untuk yang di luar Kaltara
        foreach ($diluarKaltara as $dl) {
            $geo = $geocoding->reverseGeocode((float) $dl->lat, (float) $dl->lng);
            $dl->kota = $geo['display'] ?? 'Tidak diketahui';
        }

        // Generate narasi ringkasan
        $totalNoise = $noisePoints->count();
        $totalIF = $ifOutliers->count();
        $totalDiluar = $diluarKaltara->count();

        $narasi = [];
        $narasi[] = "Dalam periode **" . Carbon::parse($tanggalAwal)->format('d M') . " — " . Carbon::parse($tanggalAkhir)->format('d M Y') . "**, ditemukan **{$totalNoise} titik terisolasi** (DBSCAN noise) dan **{$totalIF} outlier multivariate** (Isolation Forest).";

        if ($totalDiluar > 0) {
            $narasi[] = "**{$totalDiluar} absensi terdeteksi dari luar wilayah Kalimantan Utara** — ini perlu perhatian prioritas.";
        }

        if ($hotspots->count() > 0) {
            $topHotspot = $hotspots->first();
            $narasi[] = "Hotspot anomali terbanyak: **{$topHotspot->lokasi}** dengan {$topHotspot->jumlah_anomali} anomali dari {$topHotspot->jumlah_pegawai} pegawai berbeda.";
        }

        if ($instansiAnomali->count() > 0) {
            $topInst = $instansiAnomali->first();
            $narasi[] = "Instansi dengan anomali ML terbanyak: **{$topInst->nama_unit}** ({$topInst->total_anomali} anomali, {$topInst->pegawai_unik} pegawai).";
        }

        // Compat: pass old variable names for map
        $clusters = $noisePoints;

        return view('analitik.clustering', compact(
            'clusters', 'ifOutliers', 'tanggalAwal', 'tanggalAkhir',
            'noisePoints', 'hotspots', 'instansiAnomali', 'diluarKaltara', 'narasi',
        ));
    }
}
