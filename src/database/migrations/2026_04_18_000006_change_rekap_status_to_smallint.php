<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Kolom status di present_rekap SIKARA disimpan sebagai integer (bukan boolean).
     * Nilai bisa 0, 1, atau angka lain (misal menit keterlambatan).
     * Ubah dari boolean ke smallint agar sync tidak gagal.
     */
    public function up(): void
    {
        $columns = ['tw', 'mkttw', 'pktw', 'plc', 'tk', 'ta', 'i', 's', 'c', 'dl', 'dsp', 'll'];

        foreach ($columns as $col) {
            DB::statement("ALTER TABLE sync_present_rekap ALTER COLUMN {$col} DROP DEFAULT");
            DB::statement("ALTER TABLE sync_present_rekap ALTER COLUMN {$col} TYPE smallint USING CASE WHEN {$col} THEN 1 ELSE 0 END");
            DB::statement("ALTER TABLE sync_present_rekap ALTER COLUMN {$col} SET DEFAULT 0");
        }
    }

    public function down(): void
    {
    }
};
