<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Semua kolom non-PK di staging tables harus nullable
        // karena data sumber SIKARA tidak konsisten
        $alterations = [
            'sync_ref_bantu_unit' => ['id_unit', 'id_lokasi'],
            'sync_present_masuk' => ['tanggal', 'id_group'],
            'sync_present_aturan' => ['id_periode', 'id_tipe'],
            'sync_present_libur' => ['tanggal'],
            'sync_present_ijin' => ['id_pegawai'],
            'sync_present_presensi' => ['id_pegawai', 'id_group'],
            'sync_present_rekap' => ['id_pegawai', 'tanggal'],
            'sync_peg_pegawai' => ['id_unit'],
            'sync_present_maps_logs' => ['id_pegawai'],
            'sync_present_device' => ['id_pegawai'],
            'sync_present_sikara_log' => ['id_pegawai'],
        ];

        foreach ($alterations as $table => $columns) {
            foreach ($columns as $col) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$col} DROP NOT NULL");
            }
        }
    }

    public function down(): void
    {
    }
};
