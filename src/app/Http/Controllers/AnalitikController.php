<?php

namespace App\Http\Controllers;

use App\Helpers\JamKerja;
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
        WHEN jenis_ijin IN ('35','34','30','36','37','38','40','41','42','50','3','12','32','51') THEN 'Cuti'
        WHEN jenis_ijin IN ('39','2','11','17','18','19','43') THEN 'Sakit'
        WHEN jenis_ijin IN ('1','10','7','8') THEN 'Izin'
        WHEN jenis_ijin IN ('48','6','24','0','9','13','14','15','16','25','31','33','49') THEN 'Dispensasi'
        WHEN jenis_ijin IN ('46','5','23','20','44') THEN 'Diklat'
        ELSE 'Lainnya'
    END";

    /**
     * ID pegawai dari unit yang masih presensi manual (dikecualikan dari TK).
     */
    private function presensiManualPegawaiIds(): \Illuminate\Support\Collection
    {
        return DB::table('sync_peg_pegawai as p')
            ->join('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('u.presensi_manual', true)
            ->pluck('p.id_pegawai');
    }

    /**
     * Query jumlah pegawai per kategori ijin untuk satu tanggal.
     */
    /**
     * ID pegawai tugas belajar aktif pada tanggal tertentu (dari SIMPEG).
     */
    private function tubelAktifIds(string $tanggal): \Illuminate\Support\Collection
    {
        return DB::table('sync_simpeg_tugas_belajar')
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where(function ($q) use ($tanggal) {
                $q->where('tanggal_selesai', '>=', $tanggal)
                  ->orWhereNull('tanggal_selesai');
            })
            ->pluck('id_pegawai')
            ->unique();
    }

    /**
     * ID pegawai cuti aktif pada tanggal tertentu (dari SIMPEG peg_cuti,
     * melengkapi data present_ijin yang mungkin belum lengkap).
     */
    private function cutiSimpegIds(string $tanggal): \Illuminate\Support\Collection
    {
        return DB::table('sync_simpeg_cuti')
            ->where('tanggal_mulai', '<=', $tanggal)
            ->where('tanggal_selesai', '>=', $tanggal)
            ->pluck('id_pegawai')
            ->unique();
    }

    private function getIjinPerTanggal(string $tanggal, ?\Illuminate\Support\Collection $excludeIds = null): object
    {
        $query = DB::table('sync_present_ijin')
            ->whereRaw("? BETWEEN tanggal_mulai AND tanggal_selesai", [$tanggal]);

        if ($excludeIds && $excludeIds->isNotEmpty()) {
            $query->whereNotIn('id_pegawai', $excludeIds);
        }

        $result = $query->selectRaw("
                COUNT(DISTINCT CASE WHEN jenis_ijin IN ('45','22','4') THEN id_pegawai END) as dinas_luar,
                COUNT(DISTINCT CASE WHEN jenis_ijin = '47' THEN id_pegawai END) as dinas_dalam,
                COUNT(DISTINCT CASE WHEN jenis_ijin IN ('35','34','30','36','37','38','40','41','42','50','3','12','32','51') THEN id_pegawai END) as cuti,
                COUNT(DISTINCT CASE WHEN jenis_ijin IN ('39','2','11','17','18','19','43') THEN id_pegawai END) as sakit,
                COUNT(DISTINCT CASE WHEN jenis_ijin IN ('1','10','7','8') THEN id_pegawai END) as izin,
                COUNT(DISTINCT CASE WHEN jenis_ijin IN ('48','6','24','0','9','13','14','15','16','25','31','33','49') THEN id_pegawai END) as dispensasi,
                COUNT(DISTINCT CASE WHEN jenis_ijin IN ('46','5','23','20','44') THEN id_pegawai END) as diklat,
                COUNT(DISTINCT id_pegawai) as total_ijin
            ")
            ->first();

        // Tambahkan pegawai tugas belajar dari SIMPEG yang belum ada di present_ijin
        $tubelIds = $this->tubelAktifIds($tanggal);
        if ($excludeIds && $excludeIds->isNotEmpty()) {
            $tubelIds = $tubelIds->diff($excludeIds);
        }

        // ID pegawai yang sudah terhitung di present_ijin
        $ijinPegawaiIds = DB::table('sync_present_ijin')
            ->whereRaw("? BETWEEN tanggal_mulai AND tanggal_selesai", [$tanggal])
            ->pluck('id_pegawai')
            ->unique();

        // Tambah cuti dari SIMPEG (peg_cuti) yang belum ada di present_ijin
        $cutiSimpegIds = $this->cutiSimpegIds($tanggal);
        if ($excludeIds && $excludeIds->isNotEmpty()) {
            $cutiSimpegIds = $cutiSimpegIds->diff($excludeIds);
        }
        $cutiBaru = $cutiSimpegIds->diff($ijinPegawaiIds)->count();
        $result->cuti += $cutiBaru;
        $result->total_ijin += $cutiBaru;

        // Tambah tubel dari SIMPEG yang belum ada di present_ijin maupun cuti SIMPEG
        $sudahTerhitung = $ijinPegawaiIds->merge($cutiSimpegIds)->unique();
        $tubelBaru = $tubelIds->diff($sudahTerhitung)->count();
        $result->diklat += $tubelBaru;
        $result->total_ijin += $tubelBaru;

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
     * Fallback: hitung dari jam_masuk vs jadwal kerja.
     *
     * Tepat waktu masuk: 06:30 s/d 07:30
     * Pulang tepat waktu: >= 16:00 (Sen-Kam), >= 16:30 (Jum)
     */
    private function statusSql(): array
    {
        return JamKerja::statusSql();
    }

    public function tren(Request $request)
    {
        $days = (int) $request->get('days', 30);
        $days = min(max($days, 7), 365);
        $start = Carbon::today()->subDays($days - 1)->toDateString();
        $end = Carbon::today()->toDateString();

        $sql = $this->statusSql();

        // Exclude pegawai dari unit presensi manual (belum digital) dari TK
        $manualIds = $this->presensiManualPegawaiIds();

        $totalPegawai = DB::table('sync_peg_pegawai')->count();

        $tren = DB::table('sync_present_rekap')
            ->selectRaw("
                tanggal,
                COUNT(*) as rekap_count,
                {$sql['hadir']} as hadir,
                {$sql['terlambat']} as terlambat,
                {$sql['pulang_cepat']} as pulang_cepat
            ")
            ->whereBetween('tanggal', [$start, $end])
            // Exclude Sabtu (6) dan Minggu (0)
            ->whereRaw("EXTRACT(DOW FROM tanggal) NOT IN (0, 6)")
            // Exclude hari libur nasional & cuti bersama
            ->whereNotIn('tanggal', DB::table('sync_present_libur')->pluck('tanggal'))
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->map(function ($r) use ($totalPegawai, $manualIds) {
                $tidakHadir = $totalPegawai - (int) $r->hadir;
                $ijin = $this->getIjinPerTanggal($r->tanggal, $manualIds);
                $tanpaKeterangan = max(0, $tidakHadir - $ijin->total_ijin);

                return [
                    'tanggal' => $r->tanggal,
                    'label' => Carbon::parse($r->tanggal)->format('d M'),
                    'total' => $totalPegawai,
                    'hadir' => (int) $r->hadir,
                    'terlambat' => (int) $r->terlambat,
                    'pulang_cepat' => (int) $r->pulang_cepat,
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
        $jamMasuk = JamKerja::MASUK_BATAS;

        // Exclude pegawai dari unit presensi manual dari TK
        $manualIds = $this->presensiManualPegawaiIds();

        $totalPegawai = DB::table('sync_peg_pegawai')->count();

        // Ringkasan hari itu
        $masukMulai = JamKerja::MASUK_MULAI;
        $batasPulangSql = JamKerja::batasPulangSql();
        $batasSiang = JamKerja::BATAS_SIANG;
        $rekapStats = DB::table('sync_present_rekap')
            ->where('tanggal', $tanggal)
            ->selectRaw("
                SUM(CASE WHEN jam_masuk IS NOT NULL OR jam_pulang IS NOT NULL THEN 1 ELSE 0 END) as hadir,
                SUM(CASE WHEN jam_masuk IS NOT NULL AND jam_masuk > '{$jamMasuk}' AND jam_masuk <= '{$batasSiang}' THEN 1 ELSE 0 END) as terlambat,
                SUM(CASE WHEN jam_pulang IS NOT NULL AND jam_pulang >= '{$batasSiang}' AND jam_pulang < {$batasPulangSql} THEN 1 ELSE 0 END) as pulang_cepat,
                SUM(CASE WHEN jam_masuk IS NOT NULL AND jam_masuk < '{$masukMulai}' THEN 1 ELSE 0 END) as diluar_jam_masuk
            ")->first();

        $ijinStats = $this->getIjinPerTanggal($tanggal, $manualIds);
        $tidakHadir = $totalPegawai - (int) ($rekapStats->hadir ?? 0);

        $summary = (object) [
            'total' => $totalPegawai,
            'hadir' => (int) ($rekapStats->hadir ?? 0),
            'terlambat' => (int) ($rekapStats->terlambat ?? 0),
            'pulang_cepat' => (int) ($rekapStats->pulang_cepat ?? 0),
            'diluar_jam_masuk' => (int) ($rekapStats->diluar_jam_masuk ?? 0),
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

        // Daftar pegawai terlambat (jam masuk > jadwal DAN <= 12:00, di atas 12:00 = absen pulang)
        $terlambat = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('r.tanggal', $tanggal)
            ->whereNotNull('r.jam_masuk')
            ->where('r.jam_masuk', '>', $jamMasuk)
            ->where('r.jam_masuk', '<=', '12:00:00')
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
            ->where('r.jam_masuk', '<=', '12:00:00')
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

        // Daftar yang tidak hadir (lupa absen salah satu masuk/pulang tetap dianggap hadir)
        $tidakHadir = DB::table('sync_peg_pegawai as p')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereNotIn('p.id_pegawai', function ($q) use ($tanggal) {
                $q->select('id_pegawai')
                  ->from('sync_present_rekap')
                  ->where('tanggal', $tanggal)
                  ->where(function ($q2) {
                      $q2->whereNotNull('jam_masuk')->orWhereNotNull('jam_pulang');
                  });
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

    public function trenTerlambat(string $tanggal)
    {
        $jamMasuk = JamKerja::MASUK_BATAS;

        // Daftar pegawai terlambat (jam masuk > jadwal DAN <= 12:00, di atas 12:00 = absen pulang)
        $pegawai = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('r.tanggal', $tanggal)
            ->whereNotNull('r.jam_masuk')
            ->where('r.jam_masuk', '>', $jamMasuk)
            ->where('r.jam_masuk', '<=', '12:00:00')
            ->select([
                'p.nama', 'p.nip', 'u.nama_unit',
                'r.jam_masuk', 'r.jam_pulang',
                'r.nama_lokasi_berangkat',
                DB::raw("EXTRACT(EPOCH FROM (r.jam_masuk::time - '{$jamMasuk}'::time)) / 60 as menit_terlambat"),
            ])
            ->orderByDesc('menit_terlambat')
            ->get();

        // Ranking instansi
        $perInstansi = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->join('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('r.tanggal', $tanggal)
            ->whereNotNull('r.jam_masuk')
            ->where('r.jam_masuk', '>', $jamMasuk)
            ->where('r.jam_masuk', '<=', '12:00:00')
            ->selectRaw("
                u.nama_unit,
                COUNT(*) as jumlah_terlambat,
                ROUND(AVG(EXTRACT(EPOCH FROM (r.jam_masuk::time - '{$jamMasuk}'::time)) / 60)) as rata_menit
            ")
            ->groupBy('u.nama_unit')
            ->orderByDesc('jumlah_terlambat')
            ->limit(20)
            ->get();

        return view('analitik.tren-terlambat', compact('tanggal', 'jamMasuk', 'pegawai', 'perInstansi'));
    }

    public function trenPulangCepat(string $tanggal)
    {
        $batasPulang = JamKerja::batasPulang($tanggal);
        $batasSiang = JamKerja::BATAS_SIANG;

        // Daftar pegawai yang pulang sebelum batas (hanya jam_pulang >= 12:00)
        $pegawai = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('r.tanggal', $tanggal)
            ->whereNotNull('r.jam_pulang')
            ->where('r.jam_pulang', '>=', $batasSiang)
            ->where('r.jam_pulang', '<', $batasPulang)
            ->select([
                'p.nama', 'p.nip', 'u.nama_unit',
                'r.jam_masuk', 'r.jam_pulang',
                'r.nama_lokasi_pulang',
                DB::raw("EXTRACT(EPOCH FROM ('{$batasPulang}'::time - r.jam_pulang::time)) / 60 as menit_lebih_awal"),
            ])
            ->orderByDesc('menit_lebih_awal')
            ->get();

        // Ranking instansi
        $perInstansi = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->join('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('r.tanggal', $tanggal)
            ->whereNotNull('r.jam_pulang')
            ->where('r.jam_pulang', '>=', $batasSiang)
            ->where('r.jam_pulang', '<', $batasPulang)
            ->selectRaw("
                u.nama_unit,
                COUNT(*) as jumlah_pulang_cepat,
                ROUND(AVG(EXTRACT(EPOCH FROM ('{$batasPulang}'::time - r.jam_pulang::time)) / 60)) as rata_menit
            ")
            ->groupBy('u.nama_unit')
            ->orderByDesc('jumlah_pulang_cepat')
            ->limit(20)
            ->get();

        return view('analitik.tren-pulang-cepat', compact('tanggal', 'batasPulang', 'pegawai', 'perInstansi'));
    }

    public function trenTidakHadir(string $tanggal)
    {
        // Pegawai yang hadir (punya jam_masuk ATAU jam_pulang — lupa salah satu tetap dianggap hadir)
        $hadirIds = DB::table('sync_present_rekap')
            ->where('tanggal', $tanggal)
            ->where(function ($q) {
                $q->whereNotNull('jam_masuk')->orWhereNotNull('jam_pulang');
            })
            ->pluck('id_pegawai');

        // Semua pegawai yang tidak hadir
        $pegawai = DB::table('sync_peg_pegawai as p')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereNotIn('p.id_pegawai', $hadirIds)
            ->select(['p.id_pegawai', 'p.nama', 'p.nip', 'u.nama_unit'])
            ->orderBy('u.nama_unit')
            ->orderBy('p.nama')
            ->get();

        // Enrichment: cek ijin per pegawai (SIKARA)
        $ijinIds = DB::table('sync_present_ijin')
            ->whereRaw("? BETWEEN tanggal_mulai AND tanggal_selesai", [$tanggal])
            ->select(['id_pegawai', DB::raw(self::IJIN_KATEGORI_SQL . ' as kategori')])
            ->get()
            ->keyBy('id_pegawai');

        // Pegawai cuti dari SIMPEG (peg_cuti)
        $cutiSimpegIds = $this->cutiSimpegIds($tanggal);

        // Pegawai tugas belajar aktif (SIMPEG)
        $tubelIds = $this->tubelAktifIds($tanggal);

        // Exclude unit presensi manual dari TK
        $manualIds = $this->presensiManualPegawaiIds();

        foreach ($pegawai as $p) {
            $p->kategori_ijin = $ijinIds->get($p->id_pegawai)?->kategori ?? null;
            if (!$p->kategori_ijin && $cutiSimpegIds->contains($p->id_pegawai)) {
                $p->kategori_ijin = 'Cuti';
            }
            if (!$p->kategori_ijin && $tubelIds->contains($p->id_pegawai)) {
                $p->kategori_ijin = 'Diklat';
            }
            $p->is_manual = $manualIds->contains($p->id_pegawai);
        }

        // Per instansi
        $perInstansi = DB::table('sync_peg_pegawai as p')
            ->join('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereNotIn('p.id_pegawai', $hadirIds)
            ->selectRaw('u.nama_unit, COUNT(*) as jumlah')
            ->groupBy('u.nama_unit')
            ->orderByDesc('jumlah')
            ->limit(20)
            ->get();

        return view('analitik.tren-tidak-hadir', compact('tanggal', 'pegawai', 'perInstansi', 'ijinIds'));
    }

    public function trenTanpaKeterangan(string $tanggal)
    {
        // Pegawai dari unit presensi manual — dikecualikan dari TK
        $manualIds = $this->presensiManualPegawaiIds();

        // Pegawai yang hadir (punya jam_masuk ATAU jam_pulang — lupa salah satu tetap dianggap hadir)
        $hadirIds = DB::table('sync_present_rekap')
            ->where('tanggal', $tanggal)
            ->where(function ($q) {
                $q->whereNotNull('jam_masuk')->orWhereNotNull('jam_pulang');
            })
            ->pluck('id_pegawai');

        // Pegawai yang punya ijin pada tanggal itu (SIKARA)
        $ijinIds = DB::table('sync_present_ijin')
            ->whereRaw("? BETWEEN tanggal_mulai AND tanggal_selesai", [$tanggal])
            ->pluck('id_pegawai')
            ->unique();

        // Pegawai cuti dari SIMPEG (peg_cuti)
        $cutiSimpegIds = $this->cutiSimpegIds($tanggal);

        // Pegawai tugas belajar aktif (SIMPEG)
        $tubelIds = $this->tubelAktifIds($tanggal);

        // Tanpa keterangan = tidak hadir DAN tidak ada ijin/cuti/tubel DAN bukan unit manual
        $excludeIds = $hadirIds->merge($ijinIds)->merge($cutiSimpegIds)->merge($tubelIds)->merge($manualIds)->unique();

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
            'cuti' => ['kode' => ['35','34','30','36','37','38','40','41','42','50','3','12','32','51'], 'label' => 'Cuti'],
            'sakit' => ['kode' => ['39','2','11','17','18','19','43'], 'label' => 'Sakit'],
            'izin' => ['kode' => ['1','10','7','8'], 'label' => 'Izin'],
            'dispensasi' => ['kode' => ['48','6','24','0','9','13','14','15','16','25','31','33','49'], 'label' => 'Dispensasi'],
            'diklat' => ['kode' => ['46','5','23','20','44'], 'label' => 'Diklat'],
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
        $metode = $request->get('metode');
        $status = $request->get('status', 'belum_direview');
        $dari = $request->get('dari');
        $sampai = $request->get('sampai');
        $instansi = $request->get('instansi');
        $sort = $request->get('sort', 'confidence');
        $dir = $request->get('dir', 'desc');

        // Whitelist sort columns
        $allowedSorts = ['detected_at', 'confidence', 'tingkat', 'tanggal'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'detected_at';
        }
        $dir = $dir === 'asc' ? 'asc' : 'desc';

        $query = DB::table('anomaly_flags as a')
            ->leftJoin('sync_peg_pegawai as p', 'a.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit');

        if ($tingkat) {
            $query->where('a.tingkat', $tingkat);
        }
        if ($jenis) {
            $query->where('a.jenis_anomali', $jenis);
        }
        if ($metode) {
            $query->where('a.metode_deteksi', $metode);
        }
        if ($dari) {
            $query->where('a.tanggal', '>=', $dari);
        }
        if ($sampai) {
            $query->where('a.tanggal', '<=', $sampai);
        }
        if ($instansi) {
            $query->where(function ($q) use ($instansi) {
                $q->where('u.nama_unit', 'ilike', "%{$instansi}%")
                  ->orWhere('a.nama_unit_snapshot', 'ilike', "%{$instansi}%");
            });
        }
        if ($status === 'corroborated') {
            $query->where('a.corroborated', true)->where('a.status_review', 'belum_direview');
        } elseif ($status === 'false_positive') {
            $query->whereIn('a.status_review', ['false_positive', 'false_positive_resolved_by_status_update']);
        } elseif ($status) {
            $query->where('a.status_review', $status);
        }

        $anomalies = $query
            ->orderBy('a.' . $sort, $dir)
            ->select([
                'a.id', 'a.id_pegawai', 'a.tanggal', 'a.jenis_anomali', 'a.tingkat', 'a.confidence',
                'a.metode_deteksi', 'a.metadata', 'a.status_review', 'a.detected_at',
                'a.corroborated',
                DB::raw('COALESCE(p.nama, a.nama_snapshot) as nama'),
                DB::raw('COALESCE(p.nip, a.nip_snapshot) as nip'),
                DB::raw('COALESCE(u.nama_unit, a.nama_unit_snapshot) as nama_unit'),
                DB::raw('(p.id_pegawai IS NULL) as pegawai_nonaktif'),
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
            'anomalies', 'tingkat', 'jenis', 'metode', 'status', 'dari', 'sampai', 'instansi',
            'sort', 'dir', 'statsByTingkat', 'statsByJenis',
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
            $pk->geo_pulang = null;
            $pk->diluar_kaltara = false;
            $pk->diluar_kaltara_pulang = false;
            if ($pk->lat_berangkat) {
                $pk->geo_berangkat = $geocoding->reverseGeocode((float) $pk->lat_berangkat, (float) $pk->long_berangkat);
                $pk->diluar_kaltara = !$geocoding->isInKaltara((float) $pk->lat_berangkat, (float) $pk->long_berangkat);
            }
            if ($pk->lat_pulang) {
                $pk->geo_pulang = $geocoding->reverseGeocode((float) $pk->lat_pulang, (float) $pk->long_pulang);
                $pk->diluar_kaltara_pulang = !$geocoding->isInKaltara((float) $pk->lat_pulang, (float) $pk->long_pulang);
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
            $jamMasukJadwal = JamKerja::MASUK_BATAS;

            $statusParts = [];
            // Cek kolom SIKARA dulu, fallback ke jam_masuk
            if ($rekap->dl == 1) $statusParts[] = 'Dinas Luar';
            if ($rekap->i == 1) $statusParts[] = 'Izin';
            if ($rekap->s == 1) $statusParts[] = 'Sakit';
            if ($rekap->c == 1) $statusParts[] = 'Cuti';
            if ($rekap->dsp == 1) $statusParts[] = 'Dispensasi';

            if (empty($statusParts)) {
                // Hitung dari jam_masuk berdasarkan aturan jam kerja
                if ($rekap->jam_masuk) {
                    if (JamKerja::isTepatWaktuMasuk($rekap->jam_masuk)) {
                        $statusParts[] = 'Tepat Waktu';
                    } elseif (JamKerja::isTerlambat($rekap->jam_masuk)) {
                        $statusParts[] = 'Terlambat (' . Carbon::parse($rekap->jam_masuk)->format('H:i') . ')';
                    } elseif (JamKerja::isDiluarJamMasuk($rekap->jam_masuk)) {
                        $statusParts[] = 'Di Luar Jam Masuk (' . Carbon::parse($rekap->jam_masuk)->format('H:i') . ')';
                    }
                } else {
                    $statusParts[] = 'Tidak Hadir (tidak ada check-in)';
                }
            }

            $masuk = $rekap->jam_masuk ? Carbon::parse($rekap->jam_masuk)->format('H:i') : '-';
            $pulang = $rekap->jam_pulang ? Carbon::parse($rekap->jam_pulang)->format('H:i') : '-';

            // Cek pulang cepat
            if ($rekap->jam_pulang) {
                $batasPulang = JamKerja::batasPulang($anomaly->tanggal);
                if ($rekap->jam_pulang >= JamKerja::BATAS_SIANG && $rekap->jam_pulang < $batasPulang) {
                    $statusParts[] = 'Pulang Cepat (' . Carbon::parse($rekap->jam_pulang)->format('H:i') . ')';
                }
            }
            $statusStr = implode(', ', $statusParts);

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
            'status_review' => 'required|in:valid,false_positive,belum_direview',
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

    public function exportAnomaliDetailPdf(int $id, \App\Services\GeocodingService $geocoding)
    {
        // Reuse logic dari detailAnomali
        $anomaly = DB::table('anomaly_flags as a')
            ->leftJoin('sync_peg_pegawai as p', 'a.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('a.id', $id)
            ->select([
                'a.*', 'p.id_unit', 'p.bebas_lokasi',
                DB::raw('COALESCE(p.nama, a.nama_snapshot) as nama'),
                DB::raw('COALESCE(p.nip, a.nip_snapshot) as nip'),
                DB::raw('COALESCE(u.nama_unit, a.nama_unit_snapshot) as nama_unit'),
                DB::raw('(p.id_pegawai IS NULL) as pegawai_nonaktif'),
            ])
            ->first();

        abort_unless($anomaly, 404);

        $meta = json_decode($anomaly->metadata, true) ?? [];

        $rekap = DB::table('sync_present_rekap')
            ->where('id_pegawai', $anomaly->id_pegawai)
            ->where('tanggal', $anomaly->tanggal)
            ->first();

        $features = DB::table('features_kehadiran_harian')
            ->where('id_pegawai', $anomaly->id_pegawai)
            ->where('tanggal', $anomaly->tanggal)
            ->first();

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

        $riwayat = DB::table('anomaly_flags')
            ->where('id_pegawai', $anomaly->id_pegawai)
            ->where('tanggal', '>=', Carbon::parse($anomaly->tanggal)->subDays(30)->toDateString())
            ->where('tanggal', '<=', $anomaly->tanggal)
            ->orderByDesc('tanggal')
            ->limit(10)
            ->get();

        $polaKehadiran = DB::table('sync_present_rekap')
            ->where('id_pegawai', $anomaly->id_pegawai)
            ->where('tanggal', '>=', Carbon::parse($anomaly->tanggal)->subDays(13)->toDateString())
            ->where('tanggal', '<=', $anomaly->tanggal)
            ->orderBy('tanggal')
            ->get();

        foreach ($polaKehadiran as $pk) {
            $pk->geo_berangkat = null;
            $pk->diluar_kaltara = false;
            if ($pk->lat_berangkat) {
                $pk->geo_berangkat = $geocoding->reverseGeocode((float) $pk->lat_berangkat, (float) $pk->long_berangkat);
                $pk->diluar_kaltara = !$geocoding->isInKaltara((float) $pk->lat_berangkat, (float) $pk->long_berangkat);
            }
        }

        $ijinPegawai = $this->getIjinPegawai($anomaly->id_pegawai, $anomaly->tanggal);

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

        $narasi = $this->generateNarasiAnomali($anomaly, $meta, $rekap, $features, $geofenceInfo, $riwayat);

        if ($lokasiAlert && $geoBerangkat) {
            $kotaBerangkat = $geoBerangkat['display'] ?? 'lokasi tidak dikenal';
            $kotaPulang = $geoPulang['display'] ?? null;

            if ($lokasiAlert === 'berangkat') {
                $narasi['lokasi_alert'] = "Koordinat check-in menunjukkan lokasi di **{$kotaBerangkat}** — di luar wilayah Kalimantan Utara.";
            } elseif ($lokasiAlert === 'pulang') {
                $narasi['lokasi_alert'] = "Koordinat check-out menunjukkan lokasi di **{$kotaPulang}** — di luar wilayah Kalimantan Utara.";
            } else {
                $narasi['lokasi_alert'] = "Check-in dari **{$kotaBerangkat}** dan check-out dari **" . ($kotaPulang ?? $kotaBerangkat) . "** — keduanya di luar wilayah Kalimantan Utara.";
            }
        }

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

        \App\Models\Integrity\AuditTrail::catat(
            auth()->id(),
            'ekspor',
            'anomaly_flags',
            $id,
            ['format' => 'pdf', 'tipe' => 'detail']
        );

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.anomali-detail-pdf', compact(
            'anomaly', 'meta', 'rekap', 'features', 'geofenceInfo', 'riwayat', 'polaKehadiran', 'narasi',
            'geoBerangkat', 'geoPulang', 'lokasiAlert', 'ijinPegawai',
        ));
        $pdf->setPaper('a4', 'portrait');

        $nama = str_replace(' ', '-', strtolower($anomaly->nama));
        return $pdf->download("anomali-{$anomaly->id}-{$nama}-{$anomaly->tanggal}.pdf");
    }

    public function exportAnomaliPdf(Request $request)
    {
        ini_set('memory_limit', '512M');

        $tingkat = $request->get('tingkat');
        $jenis = $request->get('jenis');
        $status = $request->get('status');
        $dari = $request->get('dari', Carbon::today()->subDays(30)->toDateString());
        $sampai = $request->get('sampai', Carbon::today()->toDateString());

        $query = DB::table('anomaly_flags as a')
            ->leftJoin('sync_peg_pegawai as p', 'a.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereBetween('a.tanggal', [$dari, $sampai]);

        if ($tingkat) $query->where('a.tingkat', $tingkat);
        if ($jenis) $query->where('a.jenis_anomali', $jenis);
        if ($status === 'false_positive') {
            $query->whereIn('a.status_review', ['false_positive', 'false_positive_resolved_by_status_update']);
        } elseif ($status) {
            $query->where('a.status_review', $status);
        }

        // Hitung total sebelum limit untuk summary
        $totalCount = (clone $query)->count();

        $anomalies = $query
            ->orderByDesc('a.confidence')
            ->select([
                'a.id', 'a.tanggal', 'a.jenis_anomali', 'a.tingkat', 'a.confidence',
                'a.metode_deteksi', 'a.status_review',
                DB::raw('COALESCE(p.nama, a.nama_snapshot) as nama'),
                DB::raw('COALESCE(p.nip, a.nip_snapshot) as nip'),
                DB::raw('COALESCE(u.nama_unit, a.nama_unit_snapshot) as nama_unit'),
                DB::raw('(p.id_pegawai IS NULL) as pegawai_nonaktif'),
            ])
            ->limit(200)
            ->get();

        $summary = [
            'dari' => $dari,
            'sampai' => $sampai,
            'total' => $totalCount,
            'ditampilkan' => $anomalies->count(),
            'tingkat1' => $anomalies->where('tingkat', 1)->count(),
            'tingkat2' => $anomalies->where('tingkat', 2)->count(),
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
            ->where('a.status_review', 'belum_direview')
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
            ->where('a.status_review', 'belum_direview')
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
            ->where('a.status_review', 'belum_direview')
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
            ->where('a.status_review', 'belum_direview')
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
            ->where('a.status_review', 'belum_direview')
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

        // Corroborated: anomali yang dikonfirmasi IF + DBSCAN
        $corroboratedCount = DB::table('anomaly_flags')
            ->where('corroborated', true)
            ->where('status_review', 'belum_direview')
            ->whereBetween('tanggal', [$tanggalAwal, $tanggalAkhir])
            ->count();

        $corroboratedPoints = DB::table('anomaly_flags as a')
            ->join('sync_present_rekap as r', function ($join) {
                $join->on('a.id_pegawai', '=', 'r.id_pegawai')
                     ->on('a.tanggal', '=', 'r.tanggal');
            })
            ->join('sync_peg_pegawai as p', 'a.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('a.corroborated', true)
            ->where('a.status_review', 'belum_direview')
            ->whereBetween('a.tanggal', [$tanggalAwal, $tanggalAkhir])
            ->whereNotNull('r.lat_berangkat')
            ->select([
                'a.id', 'a.tanggal', 'r.lat_berangkat as lat', 'r.long_berangkat as lng',
                'a.confidence', 'p.nama', 'p.nip', 'u.nama_unit',
            ])
            ->groupBy('a.id', 'a.tanggal', 'r.lat_berangkat', 'r.long_berangkat',
                       'a.confidence', 'p.nama', 'p.nip', 'u.nama_unit')
            ->limit(200)
            ->get();

        // WFA di luar Kaltara — bukan anomali tapi perlu perhatian
        // Query langsung dari rekap, bukan dari anomaly_flags
        $wfaDiluarKaltara = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->whereBetween('r.tanggal', [$tanggalAwal, $tanggalAkhir])
            ->whereNotNull('r.lat_berangkat')
            ->where(function ($q) {
                $q->whereRaw("UPPER(r.nama_lokasi_berangkat) IN ('WORK FROM ANYWHERE', 'W F A')")
                  ->orWhereRaw("UPPER(r.nama_lokasi_pulang) IN ('WORK FROM ANYWHERE', 'W F A')");
            })
            ->where(function ($q) {
                $q->where('r.lat_berangkat', '<', 1.0)
                  ->orWhere('r.lat_berangkat', '>', 4.5)
                  ->orWhere('r.long_berangkat', '<', 115.0)
                  ->orWhere('r.long_berangkat', '>', 118.0);
            })
            ->select([
                'r.id_pegawai', 'r.tanggal', 'p.nama', 'p.nip', 'u.nama_unit',
                'r.lat_berangkat as lat', 'r.long_berangkat as lng',
                'r.nama_lokasi_berangkat',
            ])
            ->orderByDesc('r.tanggal')
            ->limit(50)
            ->get();

        foreach ($wfaDiluarKaltara as $w) {
            $geo = $geocoding->reverseGeocode((float) $w->lat, (float) $w->lng);
            $w->kota = $geo['display'] ?? 'Tidak diketahui';
        }

        // Generate narasi ringkasan
        $totalNoise = $noisePoints->count();
        $totalIF = $ifOutliers->count();
        $totalDiluar = $diluarKaltara->count();
        $totalWfaDiluar = $wfaDiluarKaltara->count();

        $narasi = [];
        $narasi[] = "Dalam periode **" . Carbon::parse($tanggalAwal)->format('d M') . " — " . Carbon::parse($tanggalAkhir)->format('d M Y') . "**, ditemukan **{$totalNoise} titik terisolasi** (DBSCAN noise) dan **{$totalIF} outlier multivariate** (Isolation Forest).";

        if ($totalDiluar > 0) {
            $narasi[] = "**{$totalDiluar} absensi terdeteksi dari luar wilayah Kalimantan Utara** — ini perlu perhatian prioritas.";
        }

        if ($totalWfaDiluar > 0) {
            $narasi[] = "**{$totalWfaDiluar} absensi WFA dari luar Kaltara** — pegawai ber-WFA tapi check-in dari luar wilayah provinsi.";
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
            'noisePoints', 'hotspots', 'instansiAnomali', 'diluarKaltara',
            'wfaDiluarKaltara', 'corroboratedCount', 'corroboratedPoints', 'narasi',
        ));
    }
}
