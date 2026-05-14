<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_simpeg_cuti', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_peg_cuti')->unique()->comment('PK dari SIMPEG peg_cuti');
            $table->unsignedBigInteger('id_pegawai')->index();
            $table->smallInteger('id_cuti')->nullable()->comment('Jenis cuti (ref ke cuti_ref)');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->smallInteger('jumlah')->nullable()->comment('Jumlah hari cuti');
            $table->string('no_sk', 100)->nullable();
            $table->date('tanggal_sk')->nullable();
            $table->text('keterangan')->nullable();
            $table->smallInteger('status')->nullable();
            $table->timestampTz('synced_at')->nullable();
            $table->string('source_checksum', 64)->nullable();
            $table->timestamps();

            $table->index(['tanggal_mulai', 'tanggal_selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_simpeg_cuti');
    }
};
