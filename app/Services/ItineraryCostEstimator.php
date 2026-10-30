<?php

namespace App\Services;

use App\Models\Itinerary;

class ItineraryCostEstimator
{
    /**
     * Estimasi biaya tiket per hari, total per orang, dan total rombongan.
     *
     * @return array{days: array<int, array{min: int, max: int}>, total: array{min: int, max: int}, travelers: int, group_total: array{min: int, max: int}}
     */
    public function estimate(Itinerary $itinerary): array
    {
        $itinerary->loadMissing('items.destination');

        $days = [];
        $total = ['min' => 0, 'max' => 0];

        foreach ($itinerary->items as $item) {
            $min = (int) ($item->destination?->price_min ?? 0);
            $max = (int) ($item->destination?->price_max ?? 0);

            $days[$item->day_number] ??= ['min' => 0, 'max' => 0];
            $days[$item->day_number]['min'] += $min;
            $days[$item->day_number]['max'] += $max;
            $total['min'] += $min;
            $total['max'] += $max;
        }

        ksort($days);

        $travelers = max(1, (int) ($itinerary->traveler_count ?? 1));

        return [
            'days' => $days,
            'total' => $total,
            'travelers' => $travelers,
            'group_total' => ['min' => $total['min'] * $travelers, 'max' => $total['max'] * $travelers],
        ];
    }
}
