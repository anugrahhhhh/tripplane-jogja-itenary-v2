<?php

namespace App\Http\Controllers\Profile;

use App\Http\Controllers\Controller;
use App\Services\Auth\TrustedDeviceManager;
use App\Services\Auth\TwoFactorAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TwoFactorSecurityController extends Controller
{
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

        if (! $this->twoFactor->verifyUserCode($user, $validated['code'])) {
            return back()->withErrors(['code' => 'Kode salah.'], 'twoFactor');
        }

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
