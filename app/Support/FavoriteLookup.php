<?php

namespace App\Support;

use App\Models\User;

/**
 * Cek "apakah destinasi ini difavoritkan user?" dengan satu query per
 * halaman, bukan satu query per kartu destinasi.
 *
 * Daftar ID favorit disimpan di atribut request yang sedang berjalan, jadi
 * otomatis hilang di request berikutnya dan tidak pernah basi antar halaman.
 * Kalau favorit berubah di tengah request, panggil forget() (dilakukan
 * otomatis lewat event model Favorite dan di FavoriteController).
 *
 * Proses yang berjalan lama di console (queue worker, scheduler) tidak punya
 * request per pekerjaan, jadi di sana selalu langsung query ke database.
 */
class FavoriteLookup
{
    private const ATTRIBUTE_PREFIX = 'favorite_lookup.';

    public static function has(?User $user, int|string|null $destinationId): bool
    {
        if (! $user || ! $user->getKey() || $destinationId === null) {
            return false;
        }

        if (! self::canMemoize()) {
            return $user->favorites()->where('destination_id', $destinationId)->exists();
        }

        return isset(self::ids($user)[(int) $destinationId]);
    }

    public static function forget(User|int|null $user = null): void
    {
        if (! self::canMemoize()) {
            return;
        }

        $attributes = request()->attributes;

        if ($user === null) {
            foreach (array_keys($attributes->all()) as $key) {
                if (str_starts_with((string) $key, self::ATTRIBUTE_PREFIX)) {
                    $attributes->remove($key);
                }
            }

            return;
        }

        $attributes->remove(self::key($user instanceof User ? $user->getKey() : $user));
    }

    /**
     * @return array<int, true>
     */
    private static function ids(User $user): array
    {
        $attributes = request()->attributes;
        $key = self::key($user->getKey());

        if (! $attributes->has($key)) {
            $ids = $user->favorites()
                ->pluck('destination_id')
                ->map(fn ($id) => (int) $id)
                ->flip()
                ->map(fn () => true)
                ->all();

            $attributes->set($key, $ids);
        }

        return $attributes->get($key);
    }

    private static function key(int|string $userId): string
    {
        return self::ATTRIBUTE_PREFIX.$userId;
    }

    private static function canMemoize(): bool
    {
        $app = app();

        return ! $app->runningInConsole() || $app->runningUnitTests();
    }
}
