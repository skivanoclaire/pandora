<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today()->toDateString();
        $weekAgo = Carbon::today()->subDays(6)->toDateString();

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

        // Cari hari kerja terakhir untuk referensi statistik
        $hariKerjaTerakhir = DB::table('sync_present_rekap')
            ->whereRaw("EXTRACT(DOW FROM tanggal) NOT IN (0, 6)")
            ->whereNotIn('tanggal', DB::table('sync_present_libur')->pluck('tanggal'))
            ->where('tanggal', '<', $today)
            ->max('tanggal');

        $tanggalStat = $isLibur && $hariKerjaTerakhir ? $hariKerjaTerakhir : $today;

        // Jam masuk jadwal aktif (untuk fallback jika tw/mkttw NULL)
        $jadwal = DB::table('sync_present_group')
            ->whereRaw('berlaku <= CURRENT_DATE AND berakhir >= CURRENT_DATE')
            ->first();
        $jamMasuk = $jadwal->sen_awal ?? '07:30:00';

        // === Stat cards (dari hari kerja terakhir jika hari ini libur) ===
        $totalPegawai = DB::table('sync_peg_pegawai')->count();

        $hadirHariIni = DB::table('sync_present_rekap')
            ->where('tanggal', $tanggalStat)
            ->where(function ($q) {
                $q->where('tw', 1)->orWhere('mkttw', 1)->orWhereNotNull('jam_masuk');
            })
            ->count();

        $terlambatHariIni = DB::table('sync_present_rekap')
            ->where('tanggal', $tanggalStat)
            ->where(function ($q) use ($jamMasuk) {
                $q->where('mkttw', 1)
                  ->orWhere(function ($q2) use ($jamMasuk) {
                      $q2->whereNotNull('jam_masuk')->where('jam_masuk', '>', $jamMasuk);
                  });
            })
            ->count();

        // Tidak hadir = total pegawai - yang hadir
        $tanpaKeterangan = $totalPegawai - $hadirHariIni;

        $persenKehadiran = $totalPegawai > 0
            ? round(($hadirHariIni / $totalPegawai) * 100, 1)
            : 0;

        // === Tren 7 hari (exclude weekend & libur) ===
        $hariLibur = DB::table('sync_present_libur')->pluck('tanggal');

        $tren7hari = DB::table('sync_present_rekap')
            ->selectRaw("tanggal, SUM(CASE
                WHEN COALESCE(tw, 0) = 1 OR COALESCE(mkttw, 0) = 1 THEN 1
                WHEN jam_masuk IS NOT NULL THEN 1
                ELSE 0
            END) as hadir")
            ->whereBetween('tanggal', [$weekAgo, $today])
            ->whereRaw("EXTRACT(DOW FROM tanggal) NOT IN (0, 6)")
            ->whereNotIn('tanggal', $hariLibur)
            ->groupBy('tanggal')
            ->orderBy('tanggal')
            ->get()
            ->map(fn ($r) => [
                'tanggal' => Carbon::parse($r->tanggal)->format('d M'),
                'tanggal_full' => $r->tanggal,
                'total' => $totalPegawai,
                'hadir' => (int) $r->hadir,
                'persen' => $totalPegawai > 0 ? round(($r->hadir / $totalPegawai) * 100, 1) : 0,
            ]);

        // Hitung delta tren (hari ini vs kemarin)
        $persenKemarin = $tren7hari->count() >= 2
            ? $tren7hari[$tren7hari->count() - 2]['persen'] ?? 0
            : 0;
        $deltaTren = round($persenKehadiran - $persenKemarin, 1);

        // === Ringkasan per OPD (top 15, dari hari kerja terakhir jika libur) ===
        $perOpd = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->join('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('r.tanggal', $tanggalStat)
            ->selectRaw("u.nama_unit, COUNT(*) as total, SUM(CASE
                WHEN COALESCE(r.tw, 0) = 1 OR COALESCE(r.mkttw, 0) = 1 THEN 1
                WHEN r.jam_masuk IS NOT NULL THEN 1
                ELSE 0
            END) as hadir")
            ->groupBy('u.nama_unit')
            ->orderByRaw('SUM(CASE
                WHEN COALESCE(r.tw, 0) = 1 OR COALESCE(r.mkttw, 0) = 1 THEN 1
                WHEN r.jam_masuk IS NOT NULL THEN 1
                ELSE 0
            END)::float / NULLIF(COUNT(*), 0) ASC')
            ->limit(15)
            ->get()
            ->map(fn ($r) => [
                'nama_unit' => $r->nama_unit,
                'total' => $r->total,
                'hadir' => $r->hadir,
                'persen' => $r->total > 0 ? round(($r->hadir / $r->total) * 100, 1) : 0,
            ]);

        // === OPD dengan alert (kehadiran < 80%) ===
        $opdAlert = $perOpd->filter(fn ($o) => $o['persen'] < 80)->count();

        // === Anomali tertinggi (confidence) ===
        $anomaliTerbaru = DB::table('anomaly_flags as a')
            ->join('sync_peg_pegawai as p', 'a.id_pegawai', '=', 'p.id_pegawai')
            ->where('a.status_review', 'belum_direview')
            ->orderByDesc('a.confidence')
            ->orderBy('a.tingkat')
            ->limit(10)
            ->select([
                'a.id', 'a.tanggal', 'a.jenis_anomali', 'a.tingkat',
                'a.confidence', 'a.metadata', 'a.detected_at',
                'p.nama', 'p.nip',
            ])
            ->get()
            ->map(function ($r) {
                $r->metadata = json_decode($r->metadata, true);
                $r->confidence_pct = round($r->confidence * 100);
                return $r;
            });

        $totalAnomali = DB::table('anomaly_flags')
            ->where('status_review', 'belum_direview')
            ->count();

        // === Peta anomali (confidence tertinggi untuk gambaran paling mengkhawatirkan) ===
        $petaAnomali = DB::table('anomaly_flags as a')
            ->join('sync_present_rekap as r', function ($join) {
                $join->on('a.id_pegawai', '=', 'r.id_pegawai')
                     ->on('a.tanggal', '=', 'r.tanggal');
            })
            ->whereNotNull('r.lat_berangkat')
            ->whereNotNull('r.long_berangkat')
            ->where('a.status_review', 'belum_direview')
            ->orderByDesc('a.confidence')
            ->orderBy('a.tingkat')
            ->limit(200)
            ->select([
                'r.lat_berangkat as lat', 'r.long_berangkat as lng',
                'a.jenis_anomali', 'a.tingkat', 'a.confidence',
            ])
            ->get();

        // === Narasi otomatis ===
        if ($isLibur) {
            $narasiHari = Carbon::today()->translatedFormat('l, d F Y');
            $narasiStatHari = Carbon::parse($tanggalStat)->translatedFormat('l, d F Y');
            $narasi = "Hari ini ({$narasiHari}) adalah **{$liburKeterangan}**. ";
            $narasi .= "Statistik yang ditampilkan adalah data hari kerja terakhir ({$narasiStatHari}) ";
            $narasi .= "dengan tingkat kehadiran {$persenKehadiran}%.";
            if ($totalAnomali > 0) {
                $narasi .= " Terdapat {$totalAnomali} anomali yang menunggu review.";
            }
        } else {
            $narasi = $this->generateNarasi(
                $persenKehadiran, $deltaTren, $opdAlert, $totalAnomali, $today,
            );
        }

        // === Sync status ===
        $lastSync = DB::table('sync_log')
            ->where('status', 'success')
            ->orderByDesc('finished_at')
            ->first();

        // === Top 10 OPD Terbaik & Terburuk (untuk pimpinan) ===
        $opdRankQuery = DB::table('sync_present_rekap as r')
            ->join('sync_peg_pegawai as p', 'r.id_pegawai', '=', 'p.id_pegawai')
            ->join('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
            ->where('r.tanggal', $tanggalStat)
            ->selectRaw("u.nama_unit, COUNT(*) as total, SUM(CASE WHEN r.jam_masuk IS NOT NULL THEN 1 ELSE 0 END) as hadir")
            ->groupBy('u.nama_unit')
            ->havingRaw('COUNT(*) >= 5');

        $opdTerburuk = (clone $opdRankQuery)
            ->orderByRaw('SUM(CASE WHEN r.jam_masuk IS NOT NULL THEN 1 ELSE 0 END)::float / NULLIF(COUNT(*), 0) ASC')
            ->limit(10)->get()
            ->map(function ($r) { $r->persen = $r->total > 0 ? round(($r->hadir / $r->total) * 100, 1) : 0; $r->tidak_hadir = $r->total - $r->hadir; return $r; });

        $opdTerbaik = (clone $opdRankQuery)
            ->orderByRaw('SUM(CASE WHEN r.jam_masuk IS NOT NULL THEN 1 ELSE 0 END)::float / NULLIF(COUNT(*), 0) DESC')
            ->limit(10)->get()
            ->map(function ($r) { $r->persen = $r->total > 0 ? round(($r->hadir / $r->total) * 100, 1) : 0; return $r; });

        return view('dashboard', compact(
            'totalPegawai', 'hadirHariIni', 'terlambatHariIni', 'tanpaKeterangan',
            'persenKehadiran', 'deltaTren', 'tren7hari', 'perOpd', 'opdAlert',
            'anomaliTerbaru', 'totalAnomali', 'petaAnomali', 'narasi', 'lastSync',
            'isLibur', 'liburKeterangan', 'tanggalStat',
            'opdTerburuk', 'opdTerbaik',
        ));
    }

    private function generateNarasi(
        float $persen, float $delta, int $opdAlert, int $totalAnomali, string $today,
    ): string {
        $hari = Carbon::parse($today)->translatedFormat('l, d F Y');
        $parts = [];

        if ($persen > 0) {
            $parts[] = "Tingkat kehadiran hari ini ({$hari}) sebesar {$persen}%";
            if ($delta > 0) {
                $parts[0] .= ", naik {$delta}% dari kemarin.";
            } elseif ($delta < 0) {
                $absDelta = abs($delta);
                $parts[0] .= ", turun {$absDelta}% dari kemarin.";
            } else {
                $parts[0] .= ", sama dengan kemarin.";
            }
        } else {
            $parts[] = "Belum ada data kehadiran untuk {$hari}.";
        }

        if ($opdAlert > 0) {
            $parts[] = "Perhatikan: {$opdAlert} OPD memiliki tingkat kehadiran di bawah 80%.";
        }

        if ($totalAnomali > 0) {
            $parts[] = "Terdapat {$totalAnomali} anomali yang menunggu review.";
        }

        return implode(' ', $parts);
    }
}
