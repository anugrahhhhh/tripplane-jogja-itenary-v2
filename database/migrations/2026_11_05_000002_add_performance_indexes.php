<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Index untuk query yang paling sering jalan:
 * - katalog/beranda/AI selalu memfilter destinasi aktif (per kategori),
 * - rating & daftar ulasan memfilter ulasan yang tampil per destinasi,
 * - detail itinerary mengurutkan item per hari,
 * - pengingat H-1 mencari itinerary berdasarkan tanggal mulai.
 */
return new class extends Migration
{
    private const INDEXES = [
        'destinations' => ['destinations_active_category_index' => ['is_active', 'category_id']],
        'reviews' => ['reviews_destination_visible_index' => ['destination_id', 'is_visible', 'created_at']],
        'itinerary_items' => ['itinerary_items_order_index' => ['itinerary_id', 'day_number', 'sort_order']],
        'itineraries' => ['itineraries_reminder_index' => ['start_date', 'reminder_sent_at']],
    ];

    public function up(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $indexes) {
                foreach ($indexes as $name => $columns) {
                    if (! Schema::hasIndex($table, $name)) {
                        $blueprint->index($columns, $name);
                    }
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::INDEXES as $table => $indexes) {
            Schema::table($table, function (Blueprint $blueprint) use ($table, $indexes) {
                foreach (array_keys($indexes) as $name) {
                    if (Schema::hasIndex($table, $name)) {
                        $blueprint->dropIndex($name);
                    }
                }
            });
        }
    }
};
