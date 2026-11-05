<?php

namespace App\Services;

use App\Models\ItineraryItem;
use App\Support\Weekday;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ScheduleValidator
{
    /**
     * Validasi satu hari itinerary: deteksi destinasi yang dikunjungi di
     * luar jam operasionalnya, dan jadwal antar destinasi yang bertabrakan.
     *
     * @param  Collection<int, ItineraryItem>  $dayItems  item pada satu hari yang sama
     * @param  CarbonInterface|null  $date  tanggal kunjungan; bila diisi, hari tutup mingguan ikut dicek
     * @return array<int, array<int, string>> daftar pesan peringatan, dikelompokkan per itinerary_item_id
     */
    public static function validateDay(Collection $dayItems, ?CarbonInterface $date = null): array
    {
        $warnings = [];

        foreach ($dayItems as $item) {
            $warnings[$item->id] = [];

            if (! self::isWithinOperatingHours($item)) {
                $warnings[$item->id][] = sprintf(
                    'Di luar jam operasional (%s - %s).',
                    Carbon::parse($item->destination->open_time)->format('H:i'),
                    Carbon::parse($item->destination->close_time)->format('H:i')
                );
            }

            $closedDays = $item->destination->closed_days;
            if ($date !== null && ! empty($closedDays) && in_array($date->isoWeekday(), array_map('intval', $closedDays), true)) {
                $warnings[$item->id][] = 'Tutup setiap hari '.Weekday::name($date->isoWeekday()).'.';
            }
        }

        $sorted = $dayItems->sortBy(fn (ItineraryItem $item) => self::toMinutes($item->start_time))->values();

        for ($i = 0; $i < $sorted->count() - 1; $i++) {
            $current = $sorted[$i];
            $next = $sorted[$i + 1];

            $currentEnd = self::toMinutes($current->start_time) + $current->duration_minutes;
            $nextStart = self::toMinutes($next->start_time);

            if ($currentEnd > $nextStart) {
                $warnings[$current->id][] = 'Jadwal bertabrakan dengan "'.$next->destination->name.'".';
                $warnings[$next->id][] = 'Jadwal bertabrakan dengan "'.$current->destination->name.'".';
            }
        }

        return $warnings;
    }

    public static function isWithinOperatingHours(ItineraryItem $item): bool
    {
        $start = self::toMinutes($item->start_time);
        $end = $start + $item->duration_minutes;

        return OperatingHoursService::isWithinWindow(
            $item->destination->open_time,
            $item->destination->close_time,
            $start,
            $end
        );
    }

    protected static function toMinutes(string $time): int
    {
        $carbon = Carbon::parse($time);

        return ((int) $carbon->format('H')) * 60 + (int) $carbon->format('i');
    }
}
