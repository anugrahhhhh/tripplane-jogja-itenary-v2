<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Pintu tunggal setelah kredensial utama (password / Google) valid:
 * memutuskan apakah user langsung masuk, harus setup 2FA, atau harus lewat challenge.
 */
class TwoFactorGate
{
    public const PENDING_TTL_MINUTES = 5;

    public function __construct(private readonly TrustedDeviceManager $trustedDevices) {}

    /**
     * @param  'password'|'google'  $method
     */
    public function pass(Request $request, User $user, string $method, string $intended): RedirectResponse
    {
        $session = $request->session();

        // Belum setup: login, lalu paksa setup. Tujuan awal disimpan agar setup berakhir di sana.
        if (! $user->hasTwoFactorEnabled()) {
            Auth::login($user);
            $session->regenerate();
            $session->put('url.intended', $intended);

            return redirect()->route('two-factor.setup');
        }

        if ($this->trustedDevices->isTrusted($user, $request)) {
            Auth::login($user);
            $session->regenerate();

            return redirect($intended);
        }

        // Belum tepercaya: belum login sampai kode benar.
        $session->regenerate();
        $session->put('two_factor.pending', [
            'user_id' => $user->id,
            'method' => $method,
            'intended' => $intended,
            'expires_at' => now()->addMinutes(self::PENDING_TTL_MINUTES)->timestamp,
        ]);

        return redirect()->route('two-factor.challenge');
    }
}
