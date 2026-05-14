<?php

namespace App\Helpers;

use Carbon\Carbon;

/**
 * Konstanta dan helper jam kerja ASN Pemprov Kaltara.
 *
 * Masuk tepat waktu : 06:30 s/d 07:30
 * Pulang tepat waktu: >= 16:00 (Senin-Kamis), >= 16:30 (Jumat)
 */
class JamKerja
{
    const MASUK_MULAI = '06:30:00';
    const MASUK_BATAS = '07:30:00';
    const PULANG_SENIN_KAMIS = '16:00:00';
    const PULANG_JUMAT = '16:30:00';
    const BATAS_SIANG = '12:00:00';

    /**
     * Batas pulang tepat waktu berdasarkan hari.
     */
    public static function batasPulang(string $tanggal): string
    {
        $dow = Carbon::parse($tanggal)->dayOfWeek; // 0=Sun, 5=Fri
        return $dow === 5 ? self::PULANG_JUMAT : self::PULANG_SENIN_KAMIS;
    }

    /**
     * Batas pulang tepat waktu berdasarkan day-of-week integer (Carbon).
     */
    public static function batasPulangByDow(int $dow): string
    {
        return $dow === 5 ? self::PULANG_JUMAT : self::PULANG_SENIN_KAMIS;
    }

    /**
     * SQL CASE untuk batas jam pulang berdasarkan hari (PostgreSQL).
     * Menggunakan EXTRACT(ISODOW FROM tanggal): 1=Senin, 5=Jumat.
     */
    public static function batasPulangSql(string $tabelAlias = ''): string
    {
        $col = $tabelAlias ? "{$tabelAlias}.tanggal" : 'tanggal';
        return "CASE WHEN EXTRACT(ISODOW FROM {$col}) = 5 THEN '16:30:00'::time ELSE '16:00:00'::time END";
    }

    /**
     * Apakah jam masuk tergolong tepat waktu (06:30 - 07:30).
     * Masuk sebelum 06:30 = di luar jam kerja, bukan TW.
     */
    public static function isTepatWaktuMasuk(?string $jamMasuk): bool
    {
        if (!$jamMasuk) return false;
        return $jamMasuk >= self::MASUK_MULAI && $jamMasuk <= self::MASUK_BATAS;
    }

    /**
     * Apakah jam masuk tergolong terlambat (setelah 07:30 s/d 12:00).
     */
    public static function isTerlambat(?string $jamMasuk): bool
    {
        if (!$jamMasuk) return false;
        return $jamMasuk > self::MASUK_BATAS && $jamMasuk <= self::BATAS_SIANG;
    }

    /**
     * Apakah jam masuk di luar jam kerja (sebelum 06:30 atau setelah 12:00).
     * Presensi di luar rentang ini bukan masuk kerja normal.
     */
    public static function isDiluarJamMasuk(?string $jamMasuk): bool
    {
        if (!$jamMasuk) return false;
        return $jamMasuk < self::MASUK_MULAI || $jamMasuk > self::BATAS_SIANG;
    }

    /**
     * SQL fragments untuk menghitung status kehadiran.
     *
     * Tepat waktu masuk: 06:30 - 07:30
     * Terlambat masuk: > 07:30 s/d 12:00
     * Di luar jam masuk: < 06:30 atau > 12:00 (tidak dihitung TW maupun terlambat)
     * Pulang tepat waktu: >= 16:00 (Sen-Kam), >= 16:30 (Jum)
     */
    public static function statusSql(string $tabelAlias = ''): array
    {
        $prefix = $tabelAlias ? "{$tabelAlias}." : '';
        $batasPulang = self::batasPulangSql($tabelAlias);
        $masukMulai = self::MASUK_MULAI;
        $masukBatas = self::MASUK_BATAS;
        $batasSiang = self::BATAS_SIANG;

        return [
            'hadir' => "SUM(CASE
                WHEN COALESCE({$prefix}tw, 0) = 1 OR COALESCE({$prefix}mkttw, 0) = 1 THEN 1
                WHEN {$prefix}jam_masuk IS NOT NULL OR {$prefix}jam_pulang IS NOT NULL THEN 1
                ELSE 0
            END)",
            'terlambat' => "SUM(CASE
                WHEN COALESCE({$prefix}mkttw, 0) = 1 AND ({$prefix}jam_masuk IS NULL OR ({$prefix}jam_masuk >= '{$masukMulai}' AND {$prefix}jam_masuk <= '{$batasSiang}')) THEN 1
                WHEN {$prefix}jam_masuk IS NOT NULL AND {$prefix}jam_masuk > '{$masukBatas}' AND {$prefix}jam_masuk <= '{$batasSiang}' THEN 1
                ELSE 0
            END)",
            'pulang_cepat' => "SUM(CASE
                WHEN {$prefix}jam_pulang IS NOT NULL AND {$prefix}jam_pulang >= '{$batasSiang}' AND {$prefix}jam_pulang < {$batasPulang} THEN 1
                ELSE 0
            END)",
            'tidak_hadir' => "SUM(CASE
                WHEN COALESCE({$prefix}tk, 0) = 1 THEN 1
                WHEN {$prefix}jam_masuk IS NULL AND {$prefix}jam_pulang IS NULL THEN 1
                ELSE 0
            END)",
        ];
    }
}
