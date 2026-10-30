<?php

namespace App\Services;

use App\Models\Itinerary;
use Illuminate\Support\Collection;

class ItineraryInsights
{
    /**
     * Siapkan data titik peta, peringatan jadwal (bentrok / di luar jam
     * operasional / hari tutup), dan estimasi jarak+waktu tempuh antar
     * destinasi berurutan, dikelompokkan per hari.
     *
     * @return array{0: array, 1: array, 2: array}
     */
    public static function build(Itinerary $itinerary): array
    {
        $mapPointsByDay = [];
        $warningsByDay = [];
        $travelInfoByDay = [];

        foreach ($itinerary->itemsByDay() as $day => $items) {
            $date = $itinerary->start_date->copy()->addDays($day - 1);

            $mapPointsByDay[$day] = $items->map(fn ($item) => [
                'lat' => $item->destination->latitude,
                'lng' => $item->destination->longitude,
                'name' => $item->destination->name,
            ])->values();

            $warningsByDay[$day] = ScheduleValidator::validateDay($items, $date);
            $travelInfoByDay[$day] = self::travelInfoBetween($items);
        }

        return [$mapPointsByDay, $warningsByDay, $travelInfoByDay];
    }

    /**
     * Jarak & estimasi waktu tempuh dari destinasi sebelumnya ke destinasi
     * ini, dikunci berdasarkan itinerary_item id "tujuan".
     */
    protected static function travelInfoBetween(Collection $items): array
    {
        $info = [];
        $itemsList = $items->values();

        for ($i = 0; $i < $itemsList->count() - 1; $i++) {
            $current = $itemsList[$i];
            $next = $itemsList[$i + 1];

            $distance = DistanceService::haversineKm(
                $current->destination->latitude,
                $current->destination->longitude,
                $next->destination->latitude,
                $next->destination->longitude
            );

            $info[$next->id] = [
                'distance_km' => round($distance, 1),
                'duration_minutes' => DistanceService::estimateDurationMinutes($distance),
            ];
        }

        return $info;
    }
}
