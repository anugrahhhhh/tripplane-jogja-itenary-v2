<?php

namespace App\Support;

class Rupiah
{
    public static function format(int $amount): string
    {
        return 'Rp '.number_format($amount, 0, ',', '.');
    }

    public static function range(int $min, int $max): string
    {
        if ($min === 0 && $max === 0) {
            return 'Gratis';
        }

        if ($min === $max) {
            return self::format($min);
        }

        return self::format($min).' – '.self::format($max);
    }
}
