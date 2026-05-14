<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sync_ref_unit', 'presensi_manual')) {
            Schema::table('sync_ref_unit', function (Blueprint $table) {
                $table->boolean('presensi_manual')
                    ->default(false)
                    ->after('operasi_24_jam')
                    ->comment('Unit masih menggunakan absensi manual — dikecualikan dari perhitungan Tanpa Keterangan');
            });
        }
    }

    public function down(): void
    {
        Schema::table('sync_ref_unit', function (Blueprint $table) {
            $table->dropColumn('presensi_manual');
        });
    }
};
