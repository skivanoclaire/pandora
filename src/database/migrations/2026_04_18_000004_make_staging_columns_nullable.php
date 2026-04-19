<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data sumber SIKARA banyak kolom yang ternyata NULL.
     * Ubah kolom-kolom staging yang NOT NULL menjadi nullable agar sync tidak gagal.
     */
    public function up(): void
    {
        $alterations = [
            'sync_peg_pegawai' => ['nip', 'nama'],
            'sync_ref_unit' => ['nama_unit'],
            'sync_ref_unit_ext' => ['nama_unit_ext'],
            'sync_ref_lokasi_unit' => ['nama_lokasi'],
            'sync_present_group' => ['nama_group'],
            'sync_fake_gps' => ['package_name'],
        ];

        foreach ($alterations as $table => $columns) {
            foreach ($columns as $col) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$col} DROP NOT NULL");
            }
        }
    }

    public function down(): void
    {
        // Tidak di-revert — data sumber memang nullable
    }
};
