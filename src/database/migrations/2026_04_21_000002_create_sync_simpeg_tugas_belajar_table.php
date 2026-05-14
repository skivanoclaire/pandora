<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_simpeg_tugas_belajar', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_belajar')->unique()->comment('PK dari SIMPEG peg_ijintugas_belajar');
            $table->unsignedBigInteger('id_pegawai')->index();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('no_sk', 50)->nullable();
            $table->date('tanggal_sk')->nullable();
            $table->smallInteger('tipe')->nullable()->comment('1=Ijin Belajar, 2=Tugas Belajar');
            $table->smallInteger('status')->nullable();
            $table->string('nama_instansi', 120)->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();

            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_simpeg_tugas_belajar');
    }
};
