<?php

namespace App\Support;

class Weekday
{
    private const NAMES = [
        1 => 'Senin',
        2 => 'Selasa',
        3 => 'Rabu',
        4 => 'Kamis',
        5 => 'Jumat',
        6 => 'Sabtu',
        7 => 'Minggu',
    ];

    /** Nama hari dari nomor hari ISO (1=Senin .. 7=Minggu). */
    public static function name(int $iso): string
    {
        return self::NAMES[$iso];
    }

    /**
     * @param  array<int, int>|null  $isoDays
     */
    public static function names(?array $isoDays): string
    {
        if (empty($isoDays)) {
            return '';
        }

        $days = array_map('intval', array_values($isoDays));
        sort($days);

        return implode(', ', array_map(fn (int $d) => self::name($d), $days));
    }

    /** @return array<int, string> */
    public static function all(): array
    {
        return self::NAMES;
    }
}
