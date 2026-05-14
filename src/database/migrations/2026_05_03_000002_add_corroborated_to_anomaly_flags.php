<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('anomaly_flags', function (Blueprint $table) {
            $table->boolean('corroborated')->default(false)->after('model_version')
                  ->comment('Di-flag oleh lebih dari satu metode ML (IF + DBSCAN)');
            $table->text('catatan_sistem')->nullable()->after('catatan_review')
                  ->comment('Catatan otomatis dari pipeline');
        });
    }

    public function down(): void
    {
        Schema::table('anomaly_flags', function (Blueprint $table) {
            $table->dropColumn(['corroborated', 'catatan_sistem']);
        });
    }
};
