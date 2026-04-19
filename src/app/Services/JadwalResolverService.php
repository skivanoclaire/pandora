<?php

namespace App\Services;

use App\Models\Staging\SyncPresentGroup;
use App\Models\Staging\SyncPresentLibur;
use App\Models\Staging\SyncPresentMasuk;
use App\Models\Staging\SyncPresentPresensi;
use Carbon\Carbon;

class JadwalResolverService
{
    /**
     * Resolusi jadwal efektif untuk satu (id_pegawai, tanggal).
     *
     * Mengikuti algoritma di DESIGN.md section 5.4:
     * 1. Cek libur
     * 2. Cek override (present_masuk)
     * 3. Cari grup jadwal efektif (present_presensi)
     * 4. Cari template jadwal (present_group)
     * 5. Ambil jam berdasarkan hari
     */
    public function resolve(int $idPegawai, string|Carbon $tanggal): array
    {
        $tanggal = Carbon::parse($tanggal);
        $tanggalStr = $tanggal->toDateString();

        // 1. Cek apakah hari libur
        $libur = SyncPresentLibur::where('tanggal', $tanggalStr)->first();
        if ($libur) {
            return [
                'tipe' => 'libur',
                'jam_masuk' => null,
                'jam_pulang' => null,
                'id_group' => null,
                'keterangan' => $libur->keterangan,
            ];
        }

        // 2. Cari grup jadwal efektif untuk pegawai pada tanggal ini
        $assignment = SyncPresentPresensi::where('id_pegawai', $idPegawai)
            ->where('cdate', '<=', $tanggalStr)
            ->orderByDesc('cdate')
            ->first();

        if (!$assignment) {
            return [
                'tipe' => 'undefined',
                'jam_masuk' => null,
                'jam_pulang' => null,
                'id_group' => null,
                'keterangan' => 'Pegawai tidak memiliki assignment grup jadwal',
            ];
        }

        $idGroup = $assignment->id_group;

        // 3. Cek override per tanggal (present_masuk)
        $override = SyncPresentMasuk::where('tanggal', $tanggalStr)
            ->where('id_group', $idGroup)
            ->first();

        if ($override) {
            return [
                'tipe' => 'override',
                'jam_masuk' => $override->masuk,
                'jam_pulang' => $override->pulang,
                'id_group' => $idGroup,
                'keterangan' => "Override jadwal: {$override->status}",
            ];
        }

        // 4. Cari template jadwal dari present_group
        $template = SyncPresentGroup::where('id_group', $idGroup)
            ->where('berlaku', '<=', $tanggalStr)
            ->where('berakhir', '>=', $tanggalStr)
            ->first();

        if (!$template) {
            // Fallback: cari template aktif umum (tanpa filter tanggal)
            $template = SyncPresentGroup::where('id_group', $idGroup)
                ->orderByDesc('berlaku')
                ->first();

            if (!$template) {
                return [
                    'tipe' => 'undefined',
                    'jam_masuk' => null,
                    'jam_pulang' => null,
                    'id_group' => $idGroup,
                    'keterangan' => "Template jadwal tidak ditemukan untuk grup {$idGroup}",
                ];
            }
        }

        // 5. Ambil jam berdasarkan hari dalam minggu
        $dayOfWeek = $tanggal->dayOfWeek; // 0=Minggu, 1=Senin, ..., 6=Sabtu
        $jam = $template->jamUntukHari($dayOfWeek);

        if ($jam === null) {
            // Hari ini tidak dijadwalkan kerja untuk grup ini
            return [
                'tipe' => 'libur',
                'jam_masuk' => null,
                'jam_pulang' => null,
                'id_group' => $idGroup,
                'keterangan' => "Tidak dijadwalkan kerja pada hari ini (grup: {$template->nama_group})",
            ];
        }

        return [
            'tipe' => 'template',
            'jam_masuk' => $jam['jam_masuk'],
            'jam_pulang' => $jam['jam_pulang'],
            'id_group' => $idGroup,
            'keterangan' => $template->nama_group,
        ];
    }
}
