<?php

namespace App\Http\Controllers;

use App\Helpers\JamKerja;
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
        $masukMulai = JamKerja::MASUK_MULAI;
        $masukBatas = JamKerja::MASUK_BATAS;
        $batasSiang = JamKerja::BATAS_SIANG;
        $batasPulangSql = JamKerja::batasPulangSql();

        $summary = DB::table('sync_present_rekap')
            ->where('tanggal', $tanggal)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN jam_masuk IS NOT NULL AND jam_masuk >= '{$masukMulai}' AND jam_masuk <= '{$masukBatas}' THEN 1 ELSE 0 END) as tw,
                SUM(CASE WHEN jam_masuk IS NOT NULL AND jam_masuk > '{$masukBatas}' AND jam_masuk <= '{$batasSiang}' THEN 1 ELSE 0 END) as mkttw,
                SUM(CASE WHEN jam_pulang IS NOT NULL AND jam_pulang >= '{$batasSiang}' AND jam_pulang < {$batasPulangSql} THEN 1 ELSE 0 END) as plc,
                SUM(CASE WHEN COALESCE(tk, 0) = 1 THEN 1 WHEN jam_masuk IS NULL AND jam_pulang IS NULL THEN 1 ELSE 0 END) as tk,
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

    public function rekapIndividu(Request $request)
    {
        $search = $request->get('search');
        $mode = $request->get('mode', 'bulan'); // bulan atau tahun
        $tahun = (int) $request->get('tahun', Carbon::today()->year);
        $bulan = (int) $request->get('bulan', Carbon::today()->month);
        $idPegawai = $request->get('pegawai');

        $tahun = max(2018, min($tahun, Carbon::today()->year));
        $bulan = max(1, min($bulan, 12));

        // Cari pegawai
        $pegawai = null;
        $results = collect();
        if ($search && !$idPegawai) {
            // Jika search adalah NIP lengkap (18 digit), langsung resolve ke pegawai
            $exactNip = DB::table('sync_peg_pegawai')->where('nip', $search)->value('id_pegawai');
            if ($exactNip) {
                $idPegawai = $exactNip;
            } else {
                $results = DB::table('sync_peg_pegawai as p')
                    ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
                    ->where(function ($q) use ($search) {
                        $q->where('p.nama', 'ilike', "%{$search}%")
                          ->orWhere('p.nip', 'like', "%{$search}%");
                    })
                    ->select(['p.id_pegawai', 'p.nip', 'p.nama', 'u.nama_unit'])
                    ->orderBy('p.nama')
                    ->limit(20)
                    ->get();
            }
        }

        if ($idPegawai) {
            $pegawai = DB::table('sync_peg_pegawai as p')
                ->leftJoin('sync_ref_unit as u', 'p.id_unit', '=', 'u.id_unit')
                ->where('p.id_pegawai', $idPegawai)
                ->select(['p.id_pegawai', 'p.nip', 'p.nama', 'u.nama_unit'])
                ->first();
        }

        $rekap = collect();
        $summary = null;
        $hariLibur = collect();
        $jamMasukJadwal = JamKerja::MASUK_BATAS;

        if ($pegawai) {

            $today = Carbon::today()->toDateString();

            if ($mode === 'bulan') {
                $startDate = Carbon::create($tahun, $bulan, 1)->startOfMonth()->toDateString();
                $endDate = Carbon::create($tahun, $bulan, 1)->endOfMonth()->toDateString();
            } else {
                $startDate = Carbon::create($tahun, 1, 1)->toDateString();
                $endDate = Carbon::create($tahun, 12, 31)->toDateString();
            }

            // Jangan tampilkan hari di masa depan
            if ($endDate > $today) {
                $endDate = $today;
            }

            // Hari libur dalam periode
            $hariLibur = DB::table('sync_present_libur')
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->pluck('keterangan', 'tanggal');

            // Data rekap pegawai
            $rekapData = DB::table('sync_present_rekap')
                ->where('id_pegawai', $pegawai->id_pegawai)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->orderBy('tanggal')
                ->get()
                ->keyBy('tanggal');

            // Data ijin pegawai
            $ijinData = DB::table('sync_present_ijin')
                ->where('id_pegawai', $pegawai->id_pegawai)
                ->where('tanggal_mulai', '<=', $endDate)
                ->where('tanggal_selesai', '>=', $startDate)
                ->get();

            // Build setiap hari dalam periode
            $current = Carbon::parse($startDate);
            $end = Carbon::parse($endDate);
            $totalHariKerja = 0;
            $totalHadir = 0;
            $totalTW = 0;
            $totalTerlambat = 0;
            $totalPulangCepat = 0;
            $totalTK = 0;
            $totalIjin = 0;
            $totalSakit = 0;
            $totalCuti = 0;
            $totalDL = 0;

            while ($current->lte($end)) {
                $tgl = $current->toDateString();
                $dow = $current->dayOfWeek; // 0=Sun, 6=Sat
                $isWeekend = in_array($dow, [0, 6]);
                $isLibur = $hariLibur->has($tgl);
                $isHariKerja = !$isWeekend && !$isLibur;

                $r = $rekapData->get($tgl);

                // Cek ijin
                $ijinHariIni = null;
                foreach ($ijinData as $ij) {
                    if ($tgl >= $ij->tanggal_mulai && $tgl <= $ij->tanggal_selesai) {
                        $ijinHariIni = $ij;
                        break;
                    }
                }

                $status = '-';
                $statusPulang = null;
                $statusColor = 'pandora-muted';
                if (!$isHariKerja) {
                    $status = $isLibur ? 'LIBUR' : 'WEEKEND';
                    $statusColor = 'pandora-muted';
                } elseif ($r && ($r->jam_masuk || $r->jam_pulang)) {
                    // Status masuk: TW hanya jika 06:30 - 07:30
                    if ($r->jam_masuk && JamKerja::isTepatWaktuMasuk($r->jam_masuk)) {
                        $status = 'TW';
                        $statusColor = 'pandora-success';
                        $totalTW++;
                    } elseif ($r->jam_masuk && JamKerja::isTerlambat($r->jam_masuk)) {
                        $status = 'TERLAMBAT';
                        $statusColor = 'pandora-gold';
                        $totalTerlambat++;
                    } elseif ($r->jam_masuk && JamKerja::isDiluarJamMasuk($r->jam_masuk)) {
                        // Masuk sebelum 06:30 atau setelah 12:00 — di luar jam masuk normal
                        $status = 'DJM';
                        $statusColor = 'pandora-danger';
                    } elseif (!$r->jam_masuk && $r->jam_pulang) {
                        // Hanya ada jam pulang tanpa jam masuk
                        $status = 'HM';
                        $statusColor = 'pandora-gold';
                    }

                    // Status pulang
                    $batasPulang = JamKerja::batasPulangByDow($dow);
                    if ($r->jam_pulang && $r->jam_pulang >= JamKerja::BATAS_SIANG && $r->jam_pulang < $batasPulang) {
                        $statusPulang = 'PLC';
                        $totalPulangCepat++;
                    }

                    $totalHadir++;
                } elseif ($ijinHariIni) {
                    $jenis = $ijinHariIni->jenis_ijin;
                    if (in_array($jenis, ['45','22','4'])) { $status = 'DL'; $statusColor = 'pandora-accent'; $totalDL++; }
                    elseif ($jenis == '47') { $status = 'DD'; $statusColor = 'pandora-accent'; $totalDL++; }
                    elseif (in_array($jenis, ['35','34','30','36','37','38','40','41','42','50','3','12','32','51'])) { $status = 'C'; $statusColor = 'pandora-accent'; $totalCuti++; }
                    elseif (in_array($jenis, ['39','2','11','17','18','19','43'])) { $status = 'S'; $statusColor = 'pandora-accent'; $totalSakit++; }
                    elseif (in_array($jenis, ['1','10','7','8'])) { $status = 'I'; $statusColor = 'pandora-accent'; $totalIjin++; }
                    elseif (in_array($jenis, ['46','5','23','20','44'])) { $status = 'DIKLAT'; $statusColor = 'pandora-accent'; $totalDL++; }
                    else { $status = 'DSP'; $statusColor = 'pandora-muted'; $totalIjin++; }
                } else {
                    $status = 'TK';
                    $statusColor = 'pandora-danger';
                    $totalTK++;
                }

                if ($isHariKerja) {
                    $totalHariKerja++;
                }

                $rekap->push((object) [
                    'tanggal' => $tgl,
                    'hari' => $current->translatedFormat('D'),
                    'is_hari_kerja' => $isHariKerja,
                    'is_weekend' => $isWeekend,
                    'is_libur' => $isLibur,
                    'keterangan_libur' => $hariLibur->get($tgl),
                    'jam_masuk' => $r->jam_masuk ?? null,
                    'jam_pulang' => $r->jam_pulang ?? null,
                    'lat_berangkat' => $r->lat_berangkat ?? null,
                    'long_berangkat' => $r->long_berangkat ?? null,
                    'nama_lokasi_berangkat' => $r->nama_lokasi_berangkat ?? null,
                    'lat_pulang' => $r->lat_pulang ?? null,
                    'long_pulang' => $r->long_pulang ?? null,
                    'nama_lokasi_pulang' => $r->nama_lokasi_pulang ?? null,
                    'status' => $status,
                    'status_pulang' => $statusPulang,
                    'status_color' => $statusColor,
                    'keterangan_ijin' => $ijinHariIni->keterangan ?? null,
                ]);

                $current->addDay();
            }

            $summary = (object) [
                'hari_kerja' => $totalHariKerja,
                'hadir' => $totalHadir,
                'tw' => $totalTW,
                'terlambat' => $totalTerlambat,
                'pulang_cepat' => $totalPulangCepat,
                'tk' => $totalTK,
                'ijin' => $totalIjin,
                'sakit' => $totalSakit,
                'cuti' => $totalCuti,
                'dl' => $totalDL,
                'persen_hadir' => $totalHariKerja > 0 ? round(($totalHadir / $totalHariKerja) * 100, 1) : 0,
            ];
        }

        return view('kehadiran.rekap-individu', compact(
            'search', 'results', 'pegawai', 'mode', 'tahun', 'bulan',
            'rekap', 'summary', 'jamMasukJadwal',
        ));
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
