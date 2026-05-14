<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Naikkan presisi kolom koordinat dari NUMERIC(10,7) ke NUMERIC(17,10).
 *
 * Alasan: SIKARA menyimpan koordinat sebagai MySQL `double` (~15 digit signifikan).
 * NUMERIC(10,7) hanya 7 desimal — menyebabkan dua koordinat yang berbeda di desimal
 * ke-8+ terlihat identik, mengganggu deteksi fake GPS (rule koordinat_berulang_identik).
 *
 * NUMERIC(17,10) memberikan 10 desimal (~0.01mm) — melebihi presisi GPS manapun,
 * sambil mempertahankan exact decimal arithmetic untuk perbandingan.
 */
return new class extends Migration
{
    private array $tables = [
        'sync_present_rekap' => ['lat_berangkat', 'long_berangkat', 'lat_pulang', 'long_pulang'],
        'sync_ref_lokasi_unit' => ['latitude', 'longitude'],
        'sync_present_maps_logs' => ['latitude', 'longitude'],
        'features_kehadiran_harian' => ['jarak_dari_geofence_berangkat', 'jarak_dari_geofence_pulang'],
    ];

    public function up(): void
    {
        // Koordinat: NUMERIC(10,7) → NUMERIC(17,10)
        foreach (['sync_present_rekap', 'sync_ref_lokasi_unit', 'sync_present_maps_logs'] as $table) {
            foreach ($this->tables[$table] as $col) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$col} TYPE NUMERIC(17,10)");
            }
        }

        // Jarak geofence: NUMERIC(10,2) → NUMERIC(12,4) (lebih presisi untuk meter)
        foreach ($this->tables['features_kehadiran_harian'] as $col) {
            DB::statement("ALTER TABLE features_kehadiran_harian ALTER COLUMN {$col} TYPE NUMERIC(12,4)");
        }
    }

    public function down(): void
    {
        foreach (['sync_present_rekap', 'sync_ref_lokasi_unit', 'sync_present_maps_logs'] as $table) {
            foreach ($this->tables[$table] as $col) {
                DB::statement("ALTER TABLE {$table} ALTER COLUMN {$col} TYPE NUMERIC(10,7)");
            }
        }

        foreach ($this->tables['features_kehadiran_harian'] as $col) {
            DB::statement("ALTER TABLE features_kehadiran_harian ALTER COLUMN {$col} TYPE NUMERIC(10,2)");
        }
    }
};
