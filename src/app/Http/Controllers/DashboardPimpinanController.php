<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DashboardPimpinanController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();

        // === Cek apakah hari ini libur/weekend ===
        $isWeekend = Carbon::today()->isWeekend();
        $hariLiburInfo = DB::table('sync_present_libur')->where('tanggal', $today)->first();
        $isLibur = $isWeekend || $hariLiburInfo;

        $liburKeterangan = null;
        if ($hariLiburInfo) {
            $liburKeterangan = $hariLiburInfo->keterangan;
        } elseif ($isWeekend) {
            $liburKeterangan = Carbon::today()->translatedFormat('l') . ' — hari libur';
        }

        // Cari hari kerja terakhir
        $hariKerjaTerakhir = DB::table('sync_present_rekap')
            ->whereRaw("EXTRACT(DOW FROM tanggal) NOT IN (0, 6)")
            ->whereNotIn('tanggal', DB::table('sync_present_libur')->pluck('tanggal'))
            ->where('tanggal', '<', $today)
            ->max('tanggal');

        $tanggalStat = $isLibur && $hariKerjaTerakhir ? $hariKerjaTerakhir : $today;

        $totalPegawai = DB::table('sync_peg_pegawai')->count();

        // === 1. Kehadiran hari kerja terakhir ===
        $hadirHariIni = DB::table('sync_present_rekap')
            ->where('tanggal', $tanggalStat)
            ->whereNotNull('jam_masuk')
            ->count();

        $persenKehadiran = $totalPegawai > 0
            ? round(($hadirHariIni / $totalPegawai) * 100, 1)
            : 0;

        // === Kehadiran minggu lalu (hari yang sama) ===
        $tanggalMingguLalu = Carbon::parse($tanggalStat)->subWeek()->toDateString();
        $hadirMingguLalu = DB::table('sync_present_rekap')
            ->where('tanggal', $tanggalMingguLalu)
            ->whereNotNull('jam_masuk')
            ->count();
        $persenMingguLalu = $totalPegawai > 0
            ? round(($hadirMingguLalu / $totalPegawai) * 100, 1)
            : 0;
        $deltaTren = round($persenKehadiran - $persenMingguLalu, 1);

        // === 2. Anomali pending ===
        $totalAnomaliPending = DB::table('anomaly_flags')
            ->where('status_review', 'belum_direview')
            ->count();

        $persenAnomaliPending = $totalPegawai > 0
            ? round(($totalAnomaliPending / $totalPegawai) * 100, 1)
            : 0;

        // === Skor Kesehatan Disiplin (0-100) ===
        // 50% kehadiran + 30% tren + 20% (100 - %anomali pending)
        $skorKehadiran = $persenKehadiran; // 0-100
        $skorTren = $deltaTren >= 0 ? min(100, 50 + $deltaTren * 5) : max(0, 50 + $deltaTren * 5); // normalize around 50
        $skorAnomali = max(0, 100 - $persenAnomaliPending);

        $skorKesehatan = round(
            ($skorKehadiran * 0.5) + ($skorTren * 0.3) + ($skorAnomali * 0.2),
            0
        );
        $skorKesehatan = max(0, min(100, $skorKesehatan));

        // === 3 & 4. Top 5 OPD Terburuk & Terbaik ===
        $opdQuery = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->join('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('r.tanggal', $tanggalStat)
            ->selectRaw("u.nama_unit, COUNT(*) as total, SUM(CASE WHEN r.jam_masuk IS NOT NULL THEN 1 ELSE 0 END) as hadir")
            ->groupBy('u.nama_unit')
            ->havingRaw('COUNT(*) >= 5'); // minimal 5 pegawai agar relevan

        $opdTerburuk = (clone $opdQuery)
            ->orderByRaw('SUM(CASE WHEN r.jam_masuk IS NOT NULL THEN 1 ELSE 0 END)::float / NULLIF(COUNT(*), 0) ASC')
            ->limit(5)
            ->get()
            ->map(function ($r) {
                $r->tidak_hadir = $r->total - $r->hadir;
                $r->persen = $r->total > 0 ? round(($r->hadir / $r->total) * 100, 1) : 0;
                return $r;
            });

        $opdTerbaik = (clone $opdQuery)
            ->orderByRaw('SUM(CASE WHEN r.jam_masuk IS NOT NULL THEN 1 ELSE 0 END)::float / NULLIF(COUNT(*), 0) DESC')
            ->limit(5)
            ->get()
            ->map(function ($r) {
                $r->tidak_hadir = $r->total - $r->hadir;
                $r->persen = $r->total > 0 ? round(($r->hadir / $r->total) * 100, 1) : 0;
                return $r;
            });

        // === 5. Alert Kritis (Tingkat 1 saja) ===
        $alertKritis = DB::table('anomaly_flags as a')
            ->join('sync_peg_pegawai as p', 'a.id_pegawai', '=', 'p.id_pegawai')
            ->where('a.status_review', 'belum_direview')
            ->where('a.tingkat', 1)
            ->orderByDesc('a.confidence')
            ->limit(5)
            ->select([
                'a.id', 'a.tanggal', 'a.jenis_anomali', 'a.tingkat',
                'a.confidence', 'a.metadata',
                'p.nama', 'p.nip',
            ])
            ->get()
            ->map(function ($r) {
                $r->metadata = json_decode($r->metadata, true);
                $r->confidence_pct = round($r->confidence * 100);
                $r->jenis_label = str_replace('_', ' ', $r->jenis_anomali);
                return $r;
            });

        $totalAlertT1 = DB::table('anomaly_flags')
            ->where('status_review', 'belum_direview')
            ->where('tingkat', 1)
            ->count();

        // Breakdown anomali T1 per jenis
        $anomaliPerJenis = DB::table('anomaly_flags')
            ->where('status_review', 'belum_direview')
            ->where('tingkat', 1)
            ->selectRaw("jenis_anomali, COUNT(*) as jumlah")
            ->groupBy('jenis_anomali')
            ->orderByDesc('jumlah')
            ->get();

        // === 6. Tren 4 Minggu ===
        $hariLibur = DB::table('sync_present_libur')->pluck('tanggal');
        $empat_minggu_lalu = Carbon::parse($tanggalStat)->subWeeks(4)->startOfWeek()->toDateString();

        $trenMingguan = DB::table('sync_present_rekap')
            ->selectRaw("DATE_TRUNC('week', tanggal) as minggu, COUNT(*) as total_rekap, SUM(CASE WHEN jam_masuk IS NOT NULL THEN 1 ELSE 0 END) as hadir")
            ->where('tanggal', '>=', $empat_minggu_lalu)
            ->where('tanggal', '<=', $tanggalStat)
            ->whereRaw("EXTRACT(DOW FROM tanggal) NOT IN (0, 6)")
            ->whereNotIn('tanggal', $hariLibur)
            ->groupByRaw("DATE_TRUNC('week', tanggal)")
            ->orderByRaw("DATE_TRUNC('week', tanggal)")
            ->get()
            ->map(function ($r) use ($totalPegawai) {
                // Count distinct working days in this week's data
                $hariKerja = DB::table('sync_present_rekap')
                    ->where('tanggal', '>=', $r->minggu)
                    ->where('tanggal', '<', Carbon::parse($r->minggu)->addWeek()->toDateString())
                    ->whereRaw("EXTRACT(DOW FROM tanggal) NOT IN (0, 6)")
                    ->distinct('tanggal')
                    ->count('tanggal');

                $expectedTotal = $totalPegawai * max($hariKerja, 1);
                $r->label = Carbon::parse($r->minggu)->format('d M');
                $r->persen = $expectedTotal > 0 ? round(($r->hadir / $expectedTotal) * 100, 1) : 0;
                return $r;
            });

        // === 7. Perbandingan Regional (group by kab/kota) ===
        $regional = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->join('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('r.tanggal', $tanggalStat)
            ->selectRaw("
                CASE
                    WHEN u.nama_unit ILIKE '%tarakan%' THEN 'Kota Tarakan'
                    WHEN u.nama_unit ILIKE '%nunukan%' THEN 'Kabupaten Nunukan'
                    WHEN u.nama_unit ILIKE '%malinau%' THEN 'Kabupaten Malinau'
                    WHEN u.nama_unit ILIKE '%bulungan%' THEN 'Kabupaten Bulungan'
                    WHEN u.nama_unit ILIKE '%tana tidung%' THEN 'Kabupaten Tana Tidung'
                    ELSE 'Provinsi Kaltara'
                END as wilayah,
                COUNT(*) as total,
                SUM(CASE WHEN r.jam_masuk IS NOT NULL THEN 1 ELSE 0 END) as hadir
            ")
            ->groupByRaw("
                CASE
                    WHEN u.nama_unit ILIKE '%tarakan%' THEN 'Kota Tarakan'
                    WHEN u.nama_unit ILIKE '%nunukan%' THEN 'Kabupaten Nunukan'
                    WHEN u.nama_unit ILIKE '%malinau%' THEN 'Kabupaten Malinau'
                    WHEN u.nama_unit ILIKE '%bulungan%' THEN 'Kabupaten Bulungan'
                    WHEN u.nama_unit ILIKE '%tana tidung%' THEN 'Kabupaten Tana Tidung'
                    ELSE 'Provinsi Kaltara'
                END
            ")
            ->orderByRaw("SUM(CASE WHEN r.jam_masuk IS NOT NULL THEN 1 ELSE 0 END)::float / NULLIF(COUNT(*), 0) DESC")
            ->get()
            ->map(function ($r) {
                $r->persen = $r->total > 0 ? round(($r->hadir / $r->total) * 100, 1) : 0;
                return $r;
            });

        // === Narasi Eksekutif ===
        $narasi = $this->generateNarasiEksekutif(
            $isLibur, $liburKeterangan, $tanggalStat, $today,
            $persenKehadiran, $hadirHariIni, $totalPegawai,
            $deltaTren, $totalAlertT1, $skorKesehatan,
        );

        // === Sync status ===
        $lastSync = DB::table('sync_log')
            ->where('status', 'success')
            ->orderByDesc('finished_at')
            ->first();

        return view('dashboard-pimpinan', compact(
            'skorKesehatan', 'persenKehadiran', 'hadirHariIni', 'totalPegawai',
            'deltaTren', 'persenMingguLalu',
            'opdTerburuk', 'opdTerbaik',
            'alertKritis', 'totalAlertT1', 'anomaliPerJenis',
            'trenMingguan', 'regional',
            'narasi', 'lastSync',
            'isLibur', 'liburKeterangan', 'tanggalStat',
        ));
    }

    private function generateNarasiEksekutif(
        bool|object|null $isLibur, ?string $liburKeterangan, string $tanggalStat, string $today,
        float $persenKehadiran, int $hadirHariIni, int $totalPegawai,
        float $deltaTren, int $totalAlertT1, int $skorKesehatan,
    ): string {
        $narasiHari = Carbon::today()->translatedFormat('l, d F Y');

        if ($isLibur) {
            $narasiStatHari = Carbon::parse($tanggalStat)->translatedFormat('l, d F Y');
            $narasi = "Hari ini **{$narasiHari}** ({$liburKeterangan}). ";
            $narasi .= "Data terakhir dari **{$narasiStatHari}**: ";
            $narasi .= "kehadiran **{$persenKehadiran}%** ({$hadirHariIni} dari {$totalPegawai} ASN).";
        } else {
            $narasi = "Hari ini **{$narasiHari}**. ";
            $narasi .= "Skor disiplin ASN Kaltara: **{$skorKesehatan}/100**. ";
            $narasi .= "Kehadiran hari kerja terakhir **{$persenKehadiran}%** ";
            $narasi .= "({$hadirHariIni} dari {$totalPegawai})";

            if ($deltaTren > 0) {
                $narasi .= ", **naik {$deltaTren}%** dari minggu sebelumnya.";
            } elseif ($deltaTren < 0) {
                $narasi .= ", **turun " . abs($deltaTren) . "%** dari minggu sebelumnya.";
            } else {
                $narasi .= ", stabil dari minggu sebelumnya.";
            }
        }

        if ($totalAlertT1 > 0) {
            $narasi .= " **{$totalAlertT1} anomali kritis** menunggu perhatian.";
        }

        return $narasi;
    }
}
