<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // =====================================================
        // ANALITIK — Feature engineering & output pipeline
        // =====================================================

        // 1. features_kehadiran_harian — Hasil feature engineering per pegawai per hari
        Schema::create('features_kehadiran_harian', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pegawai')->index();
            $table->date('tanggal')->index();

            // Fitur gerak & lokasi
            $table->decimal('velocity_berangkat_pulang', 8, 2)->nullable()->comment('km/jam');
            $table->decimal('velocity_vs_kemarin', 8, 2)->nullable()->comment('km/jam antar sesi berurutan');
            $table->decimal('jarak_dari_geofence_berangkat', 10, 2)->nullable()->comment('meter');
            $table->decimal('jarak_dari_geofence_pulang', 10, 2)->nullable()->comment('meter');
            $table->enum('geofence_match_flag', ['match', 'no_match', 'ambiguous', 'exempt'])->nullable();
            $table->boolean('aplikasi_fake_gps_terdeteksi')->default(false);

            // Konteks jadwal efektif (hasil resolve_jadwal)
            $table->unsignedBigInteger('id_group_efektif')->nullable();
            $table->enum('sumber_jadwal', ['libur', 'override', 'template', 'undefined'])->nullable();
            $table->time('jam_masuk_ekspektasi')->nullable();
            $table->time('jam_pulang_ekspektasi')->nullable();

            // Fitur temporal (relatif terhadap jadwal)
            $table->decimal('deviasi_masuk_vs_jadwal_ekspektasi', 8, 2)->nullable()->comment('menit');
            $table->decimal('deviasi_pulang_vs_jadwal_ekspektasi', 8, 2)->nullable()->comment('menit');
            $table->decimal('deviasi_waktu_masuk_vs_median_personal', 8, 2)->nullable()->comment('menit');
            $table->decimal('deviasi_waktu_masuk_vs_median_unit', 8, 2)->nullable()->comment('menit');

            // Snapshot status SIKARA
            $table->boolean('status_sikara_tw')->default(false);
            $table->boolean('status_sikara_mkttw')->default(false);
            $table->boolean('status_sikara_pktw')->default(false);
            $table->boolean('status_sikara_plc')->default(false);
            $table->boolean('status_sikara_tk')->default(false);
            $table->boolean('status_sikara_ta')->default(false);
            $table->enum('alasan_ketidakhadiran', ['i', 's', 'c', 'dl', 'dsp'])->nullable();

            // Hasil rule engine
            $table->enum('rule_compliance_flag', [
                'compliant', 'violation', 'no_rule_applicable', 'pending_status_finalization',
            ])->nullable();

            // Versioning & lifecycle
            $table->boolean('status_data_final')->default(false)->comment('true setelah pipeline bulanan');
            $table->uuid('computed_at_run_id')->nullable()->comment('ID run pipeline');
            $table->timestamps();

            $table->unique(['id_pegawai', 'tanggal', 'computed_at_run_id'], 'fkh_pegawai_tanggal_run');
            $table->index(['tanggal', 'status_data_final']);
        });

        // 2. anomaly_flags — Output pipeline ML + rule engine
        Schema::create('anomaly_flags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pegawai')->index();
            $table->date('tanggal')->index();
            $table->enum('jenis_anomali', [
                'fake_gps', 'geofence_violation', 'velocity_outlier',
                'temporal_outlier', 'combination',
            ]);
            $table->decimal('confidence', 5, 4)->comment('0.0000 — 1.0000');
            $table->smallInteger('tingkat')->comment('1=physical impossibility, 2=rule violation, 3=statistical, 4=false positive candidate');
            $table->enum('metode_deteksi', [
                'isolation_forest', 'dbscan', 'rule_engine', 'combination',
            ]);
            $table->string('model_version', 30)->nullable();
            $table->jsonb('metadata')->nullable()->comment('Detail fitur yang memicu');
            $table->enum('status_review', [
                'belum_direview', 'valid', 'false_positive',
                'false_positive_resolved_by_status_update',
            ])->default('belum_direview');
            $table->foreignId('direview_oleh')->nullable()->constrained('users');
            $table->timestampTz('direview_pada')->nullable();
            $table->text('catatan_review')->nullable();
            $table->timestampTz('detected_at');
            $table->timestamps();

            $table->index(['id_pegawai', 'tanggal']);
            $table->index(['status_review', 'tingkat']);
        });

        // =====================================================
        // INTEGRITY LAYER — Hash-chain & anchoring
        // =====================================================

        // 3. log_presensi_pandora — Salinan append-only untuk hash-chain
        Schema::create('log_presensi_pandora', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_pegawai')->index();
            $table->string('nip', 18)->index();
            $table->timestampTz('waktu');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('status', 50)->nullable();
            $table->string('device_id', 100)->nullable();
            $table->string('source', 50)->comment('sikara_sync / manual / correction');
            // Hash-chain columns
            $table->binary('hash_current')->nullable();
            $table->binary('hash_prev')->nullable();
            $table->bigInteger('sequence_no')->nullable()->unique();
            // Soft-invalidation (append-only: no UPDATE/DELETE fisik)
            $table->timestampTz('invalidated_at')->nullable();
            $table->text('invalidation_reason')->nullable();
            $table->foreignId('invalidated_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index('invalidated_at');
        });

        // 4. ledger_anchor — Bukti anchoring harian ke Bitcoin via OTS
        Schema::create('ledger_anchor', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal')->unique();
            $table->integer('jumlah_record');
            $table->binary('merkle_root');
            $table->bigInteger('sequence_start');
            $table->bigInteger('sequence_end');
            $table->binary('ots_proof_incomplete')->nullable();
            $table->binary('ots_proof_complete')->nullable();
            $table->string('btc_block_hash', 64)->nullable();
            $table->bigInteger('btc_block_height')->nullable();
            $table->timestampTz('anchored_at')->nullable();
            $table->timestampTz('confirmed_at')->nullable();
            $table->enum('status', ['pending', 'anchored', 'confirmed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // 5. audit_trail_pandora — Log aksi pengguna di PANDORA
        Schema::create('audit_trail_pandora', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->string('aksi', 100)->comment('login / lihat_data / ubah_geofence / review_anomali / ekspor');
            $table->string('entitas', 100)->nullable()->comment('Nama model/tabel yang terlibat');
            $table->unsignedBigInteger('entitas_id')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestampTz('created_at');

            $table->index(['user_id', 'created_at']);
            $table->index('aksi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_trail_pandora');
        Schema::dropIfExists('ledger_anchor');
        Schema::dropIfExists('log_presensi_pandora');
        Schema::dropIfExists('anomaly_flags');
        Schema::dropIfExists('features_kehadiran_harian');
    }
};
