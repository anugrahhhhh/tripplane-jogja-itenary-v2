<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\Auth\TrustedDeviceManager;
use App\Services\Auth\TwoFactorAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class TwoFactorSecurityController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function __construct(
        private readonly TwoFactorAuthenticator $twoFactor,
        private readonly TrustedDeviceManager $trustedDevices,
    ) {}

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('twoFactor', [
            'code' => ['required', 'string', 'max:64'],
        ]);

        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.setup');
        }

        $key = 'two-factor-regenerate:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            return back()->withErrors([
                'code' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ], 'twoFactor');
        }

        // Percobaan dihitung sebelum verifikasi; dibersihkan lagi bila berhasil.
        RateLimiter::hit($key, self::DECAY_SECONDS);

        if (! $this->twoFactor->verifyUserCode($user, $validated['code'])) {
            return back()->withErrors(['code' => 'Kode salah.'], 'twoFactor');
        }

        RateLimiter::clear($key);

        $codes = $this->twoFactor->generateRecoveryCodes();
        $user->forceFill(['two_factor_recovery_codes' => $codes['hashed']])->save();

        // Setelah dikonfirmasi, kembali ke halaman profil
        $request->session()->put('url.intended', route('profile.edit'));
        $request->session()->flash('two_factor.recovery_codes_plain', $codes['plain']);

        return redirect()->route('two-factor.recovery-codes');
    }

    public function destroyDevice(Request $request, int $trustedDevice): RedirectResponse
    {
        $device = $request->user()->trustedDevices()->findOrFail($trustedDevice);

        $this->trustedDevices->revoke($device);

        return back()->with('security_status', 'perangkat-dicabut');
    }

    public function destroyAllDevices(Request $request): RedirectResponse
    {
        $this->trustedDevices->revokeAll($request->user());

        return back()->with('security_status', 'semua-perangkat-dicabut');
    }
}
