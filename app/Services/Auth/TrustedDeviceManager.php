<?php

namespace App\Services\Auth;

use App\Models\TrustedDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class TrustedDeviceManager
{
    public const COOKIE = 'trusted_device';

    public const DAYS = 30;

    /**
     * Daftarkan perangkat ini: DB hanya menyimpan SHA-256, token mentah hanya ada di cookie.
     */
    public function trust(User $user, Request $request): TrustedDevice
    {
        $token = Str::random(40);

        $device = $user->trustedDevices()->create([
            'token_hash' => hash('sha256', $token),
            'user_agent' => Str::limit((string) $request->userAgent(), 512, ''),
            'ip_address' => $request->ip(),
            'expires_at' => now()->addDays(self::DAYS),
        ]);

        Cookie::queue(Cookie::make(
            self::COOKIE,
            $token,
            60 * 24 * self::DAYS,
            '/',
            null,
            (bool) config('session.secure'),
            true,
            false,
            'lax',
        ));

        return $device;
    }

    public function isTrusted(User $user, Request $request): bool
    {
        $token = $request->cookie(self::COOKIE);

        if (! is_string($token) || $token === '') {
            return false;
        }

        $device = $user->trustedDevices()
            ->where('token_hash', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();

        if (! $device) {
            Cookie::queue(Cookie::forget(self::COOKIE));

            return false;
        }

        $device->forceFill(['last_used_at' => now()])->save();

        return true;
    }

    public function revoke(TrustedDevice $device): void
    {
        $device->delete();
    }

    public function revokeAll(User $user): int
    {
        return $user->trustedDevices()->delete();
    }
}
