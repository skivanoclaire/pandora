<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Data sumber SIKARA memiliki unit dengan nama_unit NULL.
     * Ubah kolom menjadi nullable agar sync ref_unit tidak gagal.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE sync_ref_unit ALTER COLUMN nama_unit DROP NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE sync_ref_unit ALTER COLUMN nama_unit SET NOT NULL");
    }
};
