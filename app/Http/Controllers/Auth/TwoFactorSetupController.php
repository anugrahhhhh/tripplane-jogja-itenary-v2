<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Notifications\TwoFactorEnabledNotification;
use App\Services\Auth\TwoFactorAuthenticator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorSetupController extends Controller
{
    public function __construct(private readonly TwoFactorAuthenticator $twoFactor) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect($this->defaultDestination($request));
        }

        // Secret dibuat sekali agar QR yang sudah dipindai tetap valid saat reload
        $secret = $request->session()->get('two_factor.setup_secret');
        if ($secret === null) {
            $secret = $this->twoFactor->generateSecret();
            $request->session()->put('two_factor.setup_secret', $secret);
        }

        return view('auth.two-factor.setup', [
            'qrSvg' => $this->twoFactor->qrCodeSvg($user, $secret),
            'secretGroups' => trim(chunk_split($secret, 4, ' ')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect($this->defaultDestination($request));
        }

        $request->validate(['code' => ['required', 'string']], [
            'code.required' => 'Masukkan kode 6 digit dari aplikasi authenticator.',
        ]);

        $secret = $request->session()->get('two_factor.setup_secret');
        if ($secret === null) {
            return redirect()->route('two-factor.setup');
        }

        $step = $this->twoFactor->verifySecretCode($secret, $request->string('code')->toString(), null);
        if ($step === null) {
            throw ValidationException::withMessages([
                'code' => 'Kode tidak valid atau sudah kedaluwarsa. Coba lagi dengan kode terbaru.',
            ]);
        }

        $codes = $this->twoFactor->generateRecoveryCodes();

        $user->forceFill([
            'two_factor_secret' => $secret,
            'two_factor_recovery_codes' => $codes['hashed'],
            'two_factor_confirmed_at' => now(),
            'two_factor_last_used_step' => $step,
        ])->save();

        $request->session()->forget('two_factor.setup_secret');
        $request->session()->put('two_factor.recovery_codes_plain', $codes['plain']);

        $user->notify(new TwoFactorEnabledNotification);

        return redirect()->route('two-factor.recovery-codes');
    }

    public function recoveryCodes(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->get('two_factor.recovery_codes_plain');

        if (empty($codes)) {
            return redirect($this->defaultDestination($request));
        }

        return view('auth.two-factor.recovery-codes', ['codes' => $codes]);
    }

    public function acknowledge(Request $request): RedirectResponse
    {
        $request->session()->forget('two_factor.recovery_codes_plain');

        return redirect()->intended($this->defaultDestination($request));
    }

    private function defaultDestination(Request $request): string
    {
        return $request->user()->isAdmin() ? route('admin.dashboard') : route('home');
    }
}
