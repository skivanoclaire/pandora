<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anomaly_flags', function (Blueprint $table) {
            $table->string('nama_snapshot', 150)->nullable()->after('id_pegawai')
                  ->comment('Snapshot nama pegawai saat deteksi (tahan terhadap delete di sync_peg_pegawai)');
            $table->string('nip_snapshot', 30)->nullable()->after('nama_snapshot')
                  ->comment('Snapshot NIP pegawai saat deteksi');
            $table->string('nama_unit_snapshot', 200)->nullable()->after('nip_snapshot')
                  ->comment('Snapshot nama unit pegawai saat deteksi');
        });

        DB::statement("
            UPDATE anomaly_flags a
            SET nama_snapshot = p.nama,
                nip_snapshot = p.nip,
                nama_unit_snapshot = u.nama_unit
            FROM sync_peg_pegawai p
            LEFT JOIN sync_ref_unit u ON p.id_unit = u.id_unit
            WHERE a.id_pegawai = p.id_pegawai
              AND a.nama_snapshot IS NULL
        ");
    }

    public function down(): void
    {
        Schema::table('anomaly_flags', function (Blueprint $table) {
            $table->dropColumn(['nama_snapshot', 'nip_snapshot', 'nama_unit_snapshot']);
        });
    }
};
