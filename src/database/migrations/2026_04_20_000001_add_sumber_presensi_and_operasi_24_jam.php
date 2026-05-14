<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambah sumber_presensi di features untuk membedakan fingerprint vs mobile
        Schema::table('features_kehadiran_harian', function (Blueprint $table) {
            $table->enum('sumber_presensi', ['fingerprint', 'mobile', 'unknown'])
                ->default('unknown')
                ->after('aplikasi_fake_gps_terdeteksi')
                ->comment('Sumber data: fingerprint (tanpa koordinat) atau mobile (dengan GPS)');
        });

        // Tambah flag operasi_24_jam di unit untuk RSUD dan sejenisnya
        Schema::table('sync_ref_unit', function (Blueprint $table) {
            $table->boolean('operasi_24_jam')
                ->default(false)
                ->after('level')
                ->comment('Unit beroperasi 24/7 (RSUD, dll) — absensi hari libur normal');
        });
    }

    public function down(): void
    {
        Schema::table('features_kehadiran_harian', function (Blueprint $table) {
            $table->dropColumn('sumber_presensi');
        });

        Schema::table('sync_ref_unit', function (Blueprint $table) {
            $table->dropColumn('operasi_24_jam');
        });
    }
};
