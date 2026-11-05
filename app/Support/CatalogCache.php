<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Cache untuk data katalog publik (kategori, destinasi populer) yang sering
 * dibaca tapi jarang berubah. Dikosongkan otomatis setiap kali Category,
 * Destination, DestinationPhoto, atau Review disimpan/dihapus (lihat
 * AppServiceProvider), jadi data yang tampil tidak pernah basi lama.
 */
class CatalogCache
{
    public const TTL_SECONDS = 600;

    public const HOME = 'catalog.home';

    public const BROWSE = 'catalog.browse';

    public const CATEGORIES = 'catalog.categories';

    private const KEYS = [self::HOME, self::BROWSE, self::CATEGORIES];

    /**
     * Ambil dari cache, atau hitung lalu simpan. Kalau server cache bermasalah,
     * data tetap diambil langsung dari database supaya halaman tidak error.
     */
    public static function remember(string $key, Closure $callback): mixed
    {
        try {
            $cached = Cache::get($key);
        } catch (Throwable $e) {
            report($e);
            $cached = null;
        }

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();

        try {
            Cache::put($key, $value, self::TTL_SECONDS);
        } catch (Throwable $e) {
            report($e);
        }

        return $value;
    }

    public static function flush(): void
    {
        foreach (self::KEYS as $key) {
            try {
                Cache::forget($key);
            } catch (Throwable $e) {
                report($e);
            }
        }
    }
}
