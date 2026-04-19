<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pastikan ekstensi PostGIS aktif
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        // =====================================================
        // MASTER DATA ASLI PANDORA
        // =====================================================

        // 1. geofence_zones — Definisi zona geografis
        Schema::create('geofence_zones', function (Blueprint $table) {
            $table->id();
            $table->string('nama_zona', 255);
            // PostGIS geometry column ditambahkan via raw SQL di bawah
            $table->decimal('lat_center', 10, 7)->nullable();
            $table->decimal('long_center', 10, 7)->nullable();
            $table->integer('radius_meter')->nullable();
            $table->boolean('aktif')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });

        // Tambah kolom geometry PostGIS
        DB::statement('ALTER TABLE geofence_zones ADD COLUMN polygon geometry(Polygon, 4326)');
        DB::statement('CREATE INDEX geofence_zones_polygon_idx ON geofence_zones USING GIST (polygon)');

        // 2. geofence_rules — Aturan kapan zona berlaku (hari/jam)
        Schema::create('geofence_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('geofence_zone_id')->constrained('geofence_zones')->cascadeOnDelete();
            $table->smallInteger('hari_of_week')->comment('0=Minggu, 1=Senin, ..., 6=Sabtu');
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->string('jenis_kegiatan', 100)->comment('apel_pagi / senam_sore / jam_kerja / dll');
            $table->date('berlaku_mulai')->nullable();
            $table->date('berlaku_sampai')->nullable();
            $table->jsonb('unit_kerja_ids')->nullable()->comment('Scope per OPD, null = semua');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['geofence_zone_id', 'hari_of_week']);
        });

        // 3. whitelist_pegawai — Pengecualian PANDORA-native
        Schema::create('whitelist_pegawai', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pegawai')->index();
            $table->string('jenis_whitelist', 100)->comment('bebas_lokasi / dispensasi_khusus / dll');
            $table->text('alasan');
            $table->date('berlaku_mulai');
            $table->date('berlaku_sampai')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whitelist_pegawai');
        Schema::dropIfExists('geofence_rules');
        Schema::dropIfExists('geofence_zones');
    }
};
