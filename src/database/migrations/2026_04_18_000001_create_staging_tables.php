<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =====================================================
        // STAGING LAYER — mirror dari SIMPEG/SIKARA (read-only)
        // Konvensi: prefix sync_, struktur mengikuti sumber
        // Tambahan: synced_at, source_checksum
        // =====================================================

        // 1. sync_peg_pegawai — Master pegawai (hanya kolom relevan)
        Schema::create('sync_peg_pegawai', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pegawai')->unique()->comment('PK dari SIKARA');
            $table->string('nip', 18)->index();
            $table->string('nama', 255);
            $table->unsignedBigInteger('id_unit')->nullable()->index();
            $table->string('status', 50)->nullable()->comment('Status kepegawaian');
            $table->boolean('bebas_lokasi')->default(false)->comment('Tugas lapangan permanen');
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();
        });

        // 2. sync_ref_unit — Master OPD/Unit Kerja
        Schema::create('sync_ref_unit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_unit')->unique()->comment('PK dari SIKARA');
            $table->string('nama_unit', 255);
            $table->unsignedBigInteger('parent_id')->nullable()->index();
            $table->string('kode_unit', 50)->nullable();
            $table->integer('level')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();
        });

        // 3. sync_ref_unit_ext — Extended unit info
        Schema::create('sync_ref_unit_ext', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_unit_ext')->unique()->comment('PK dari SIKARA');
            $table->unsignedBigInteger('id_unit')->index();
            $table->string('nama_unit_ext', 255)->nullable();
            $table->text('alamat')->nullable();
            $table->string('telepon', 50)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();
        });

        // 4. sync_present_rekap — Sumber utama data kehadiran
        Schema::create('sync_present_rekap', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_rekap')->unique()->comment('PK dari SIKARA');
            $table->unsignedBigInteger('id_pegawai')->index();
            $table->date('tanggal')->index();
            $table->string('nip', 18)->nullable()->index();
            // Lokasi berangkat
            $table->decimal('lat_berangkat', 10, 7)->nullable();
            $table->decimal('long_berangkat', 10, 7)->nullable();
            $table->string('nama_lokasi_berangkat', 255)->nullable();
            $table->string('foto_berangkat', 500)->nullable();
            // Lokasi pulang
            $table->decimal('lat_pulang', 10, 7)->nullable();
            $table->decimal('long_pulang', 10, 7)->nullable();
            $table->string('nama_lokasi_pulang', 255)->nullable();
            $table->string('foto_pulang', 500)->nullable();
            // Waktu
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            // Status kehadiran (klasifikasi output SIKARA)
            $table->boolean('tw')->default(false)->comment('Tepat Waktu');
            $table->boolean('mkttw')->default(false)->comment('Masuk Kerja Tidak Tepat Waktu');
            $table->boolean('pktw')->default(false)->comment('Pulang Kerja Tepat Waktu');
            $table->boolean('plc')->default(false)->comment('Pulang Lebih Cepat');
            $table->boolean('tk')->default(false)->comment('Tanpa Kehadiran');
            $table->boolean('ta')->default(false)->comment('Tidak Absen');
            // Alasan ketidakhadiran
            $table->boolean('i')->default(false)->comment('Izin');
            $table->boolean('s')->default(false)->comment('Sakit');
            $table->boolean('c')->default(false)->comment('Cuti');
            $table->boolean('dl')->default(false)->comment('Dinas Luar');
            $table->boolean('dsp')->default(false)->comment('Dispensasi');
            $table->boolean('ll')->default(false)->comment('Libur');
            $table->string('d', 10)->nullable()->comment('Status belum diketahui - butuh discovery');
            // Jenis presensi
            $table->string('jenis_presensi', 50)->nullable();
            // Timestamps sumber
            $table->timestampTz('cdate')->nullable()->comment('Created date dari SIKARA');
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();

            $table->index(['id_pegawai', 'tanggal']);
        });

        // 5. sync_present_sikara_log — Log mentah dengan JSON payload
        Schema::create('sync_present_sikara_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_log')->unique()->comment('PK dari SIKARA');
            $table->unsignedBigInteger('id_pegawai')->index();
            $table->string('nip', 18)->nullable()->index();
            $table->jsonb('data')->nullable()->comment('Payload JSON mentah — perlu discovery');
            $table->timestampTz('cdate')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();
        });

        // 6. sync_present_device — Registrasi device (IMEI)
        Schema::create('sync_present_device', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_device')->unique()->comment('PK dari SIKARA');
            $table->unsignedBigInteger('id_pegawai')->index();
            $table->string('imei', 50)->nullable();
            $table->string('nama_device', 255)->nullable();
            $table->string('model', 255)->nullable();
            $table->timestampTz('cdate')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();
        });

        // 7. sync_ref_lokasi_unit — Master geofence SIKARA (lat/long + radius)
        Schema::create('sync_ref_lokasi_unit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_lokasi')->unique()->comment('PK dari SIKARA');
            $table->string('nama_lokasi', 255);
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->integer('radius')->nullable()->comment('Radius dalam meter');
            $table->boolean('aktif')->default(true);
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();
        });

        // 8. sync_ref_bantu_unit — Mapping unit kerja ↔ lokasi absensi
        Schema::create('sync_ref_bantu_unit', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_bantu')->unique()->comment('PK dari SIKARA');
            $table->unsignedBigInteger('id_unit')->index();
            $table->unsignedBigInteger('id_lokasi')->index();
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();
        });

        // 9. sync_present_group — Template jadwal kerja per grup
        Schema::create('sync_present_group', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_group')->unique()->comment('PK dari SIKARA');
            $table->string('nama_group', 255);
            $table->date('berlaku')->nullable()->comment('Tanggal mulai berlaku');
            $table->date('berakhir')->nullable()->comment('Tanggal berakhir');
            // Jam per hari (awal/akhir)
            $table->time('sen_awal')->nullable();
            $table->time('sen_akhir')->nullable();
            $table->time('sel_awal')->nullable();
            $table->time('sel_akhir')->nullable();
            $table->time('rab_awal')->nullable();
            $table->time('rab_akhir')->nullable();
            $table->time('kam_awal')->nullable();
            $table->time('kam_akhir')->nullable();
            $table->time('jum_awal')->nullable();
            $table->time('jum_akhir')->nullable();
            $table->time('sab_awal')->nullable();
            $table->time('sab_akhir')->nullable();
            $table->time('min_awal')->nullable();
            $table->time('min_akhir')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();
        });

        // 10. sync_present_presensi — Mapping pegawai → grup jadwal
        Schema::create('sync_present_presensi', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_presensi')->unique()->comment('PK dari SIKARA');
            $table->unsignedBigInteger('id_pegawai')->index();
            $table->unsignedBigInteger('id_group')->index();
            $table->timestampTz('cdate')->nullable()->comment('Kapan assignment dibuat');
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();

            $table->index(['id_pegawai', 'cdate']);
        });

        // 11. sync_present_masuk — Override jadwal per tanggal spesifik
        Schema::create('sync_present_masuk', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_masuk')->unique()->comment('PK dari SIKARA');
            $table->date('tanggal');
            $table->unsignedBigInteger('id_group')->index();
            $table->string('status', 50)->nullable();
            $table->time('masuk')->nullable();
            $table->time('pulang')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();

            $table->index(['tanggal', 'id_group']);
        });

        // 12. sync_present_aturan — Parameter toleransi per periode
        Schema::create('sync_present_aturan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_aturan')->unique()->comment('PK dari SIKARA');
            $table->unsignedBigInteger('id_periode')->nullable()->index();
            $table->unsignedBigInteger('id_tipe')->nullable();
            $table->string('nilai', 255)->nullable()->comment('Nilai threshold — semantik perlu discovery');
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();
        });

        // 13. sync_present_libur — Daftar hari libur
        Schema::create('sync_present_libur', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_libur')->unique()->comment('PK dari SIKARA');
            $table->date('tanggal')->index();
            $table->string('keterangan', 255)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();
        });

        // 14. sync_present_ijin — Ijin per pegawai
        Schema::create('sync_present_ijin', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_ijin')->unique()->comment('PK dari SIKARA');
            $table->unsignedBigInteger('id_pegawai')->index();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('jenis_ijin', 100)->nullable();
            $table->text('keterangan')->nullable();
            $table->timestampTz('cdate')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();
        });

        // 15. sync_present_maps_logs — Log granular per sesi absensi
        Schema::create('sync_present_maps_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_maps_log')->unique()->comment('PK dari SIKARA');
            $table->unsignedBigInteger('id_pegawai')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->time('jam')->nullable();
            $table->integer('jamke')->nullable();
            $table->unsignedBigInteger('id_maps')->nullable()->index();
            $table->date('tanggal')->nullable()->index();
            $table->timestampTz('cdate')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();
        });

        // 16. sync_fake_gps — Daftar package aplikasi Fake GPS
        Schema::create('sync_fake_gps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_fake_gps')->unique()->comment('PK dari SIKARA');
            $table->string('package_name', 255);
            $table->string('nama_aplikasi', 255)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();
        });

        // 17. sync_log — Log operasi sinkronisasi
        Schema::create('sync_log', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_sumber', 100)->comment('Nama tabel SIKARA yang di-sync');
            $table->string('tabel_tujuan', 100)->comment('Nama tabel staging PANDORA');
            $table->timestampTz('started_at');
            $table->timestampTz('finished_at')->nullable();
            $table->integer('rows_fetched')->default(0);
            $table->integer('rows_inserted')->default(0);
            $table->integer('rows_updated')->default(0);
            $table->enum('status', ['running', 'success', 'failed'])->default('running');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_log');
        Schema::dropIfExists('sync_fake_gps');
        Schema::dropIfExists('sync_present_maps_logs');
        Schema::dropIfExists('sync_present_ijin');
        Schema::dropIfExists('sync_present_libur');
        Schema::dropIfExists('sync_present_aturan');
        Schema::dropIfExists('sync_present_masuk');
        Schema::dropIfExists('sync_present_presensi');
        Schema::dropIfExists('sync_present_group');
        Schema::dropIfExists('sync_ref_bantu_unit');
        Schema::dropIfExists('sync_ref_lokasi_unit');
        Schema::dropIfExists('sync_present_device');
        Schema::dropIfExists('sync_present_sikara_log');
        Schema::dropIfExists('sync_present_rekap');
        Schema::dropIfExists('sync_ref_unit_ext');
        Schema::dropIfExists('sync_ref_unit');
        Schema::dropIfExists('sync_peg_pegawai');
    }
};
