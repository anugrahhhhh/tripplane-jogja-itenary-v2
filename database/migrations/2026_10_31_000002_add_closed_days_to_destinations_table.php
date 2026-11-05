<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            // Hari tutup mingguan (ISO 1=Senin .. 7=Minggu); null = buka setiap hari.
            $table->json('closed_days')->nullable()->after('close_time');
        });
    }

    public function down(): void
    {
        Schema::table('destinations', function (Blueprint $table) {
            $table->dropColumn('closed_days');
        });
    }
};
