<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_data_changes', function (Blueprint $table) {
            $table->id();
            $table->string('tabel_sumber', 100);
            $table->string('pk_value', 100)->comment('PK value of changed record');
            $table->date('tanggal')->nullable()->comment('Record date if applicable');
            $table->string('old_checksum', 64);
            $table->string('new_checksum', 64);
            $table->jsonb('changed_fields')->nullable();
            $table->enum('severity', ['info', 'warning', 'critical'])->default('info');
            $table->boolean('reviewed')->default(false);
            $table->timestamps();
            $table->index(['tabel_sumber', 'tanggal']);
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_data_changes');
    }
};
