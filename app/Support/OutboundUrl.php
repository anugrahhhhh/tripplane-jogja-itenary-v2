<?php

namespace App\Support;

use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Log;

/**
 * Buat URL yang dikirim keluar aplikasi (email, notifikasi) dari APP_URL,
 * bukan dari header Host request.
 *
 * Kenapa: Host dikirim oleh klien. Tanpa ini, penyerang bisa meminta reset
 * password untuk korban dengan "Host: situs-penyerang", lalu email korban
 * berisi link ke situs penyerang beserta token resetnya (password reset
 * poisoning). Browsing biasa sengaja TIDAK diubah, supaya situs tetap jalan
 * walau dibuka lewat alamat lain (mis. IP server atau 127.0.0.1).
 */
final class OutboundUrl
{
    /**
     * Jalankan $callback dengan semua URL dibuat dari APP_URL, lalu kembalikan
     * pengaturan URL seperti semula (juga saat $callback melempar exception).
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public static function using(callable $callback): mixed
    {
        $generator = app('url');
        $root = self::root();

        if ($root === null || ! $generator instanceof UrlGenerator) {
            return $callback();
        }

        [$previousRoot, $previousScheme] = (fn () => [$this->forcedRoot, $this->forceScheme])->call($generator);

        $generator->useOrigin($root);
        $generator->forceScheme((string) parse_url($root, PHP_URL_SCHEME));

        try {
            return $callback();
        } finally {
            $generator->useOrigin($previousRoot);
            (function () use ($previousScheme) {
                $this->forceScheme = $previousScheme;
                $this->cachedScheme = null;
            })->call($generator);
        }
    }

    /**
     * APP_URL tanpa garis miring di akhir, atau null kalau tidak valid.
     */
    public static function root(): ?string
    {
        $root = rtrim(trim((string) config('app.url')), '/');
        $scheme = parse_url($root, PHP_URL_SCHEME);

        if ($root === '' || ! filter_var($root, FILTER_VALIDATE_URL) || ! in_array($scheme, ['http', 'https'], true)) {
            Log::warning('APP_URL kosong/tidak valid; link di email memakai host dari request.', ['app_url' => $root]);

            return null;
        }

        return $root;
    }
}
