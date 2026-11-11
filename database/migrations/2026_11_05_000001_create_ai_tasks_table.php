<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permintaan AI (susun itinerary / rekomendasi) yang diproses di latar
 * belakang lewat queue, supaya request web tidak tertahan menunggu Gemini.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            // Pemilik tamu (rekomendasi AI bisa dipakai tanpa login): hash ID sesi.
            $table->char('guest_token', 64)->nullable()->index();
            $table->string('type', 20);
            $table->string('status', 20)->default('queued');
            $table->json('input');
            $table->json('result')->nullable();
            $table->foreignId('itinerary_id')->nullable()->constrained()->nullOnDelete();
            $table->string('error', 500)->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_tasks');
    }
};
