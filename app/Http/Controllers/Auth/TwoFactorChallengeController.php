<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\NewLoginNotification;
use App\Notifications\RecoveryCodeUsedNotification;
use App\Services\Auth\TrustedDeviceManager;
use App\Services\Auth\TwoFactorAuthenticator;
use App\Support\UserAgentSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorChallengeController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    private const DECAY_SECONDS = 60;

    public function __construct(
        private readonly TwoFactorAuthenticator $twoFactor,
        private readonly TrustedDeviceManager $trustedDevices,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $resolved = $this->resolvePending($request);

        return $resolved instanceof RedirectResponse ? $resolved : view('auth.two-factor.challenge');
    }

    public function store(Request $request): RedirectResponse
    {
        $resolved = $this->resolvePending($request);

        if ($resolved instanceof RedirectResponse) {
            return $resolved;
        }

        [$pending, $user] = $resolved;

        $request->validate([
            'code' => ['nullable', 'string', 'max:64'],
            'recovery_code' => ['nullable', 'string', 'max:64'],
        ]);

        $code = trim($request->string('code')->toString());
        $recoveryCode = trim($request->string('recovery_code')->toString());

        if (($code === '') === ($recoveryCode === '')) {
            throw ValidationException::withMessages([
                'code' => 'Masukkan kode dari aplikasi authenticator atau satu recovery code.',
            ]);
        }

        $key = 'two-factor:'.$user->id.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            throw ValidationException::withMessages([
                'code' => 'Terlalu banyak percobaan. Coba lagi dalam '.RateLimiter::availableIn($key).' detik.',
            ]);
        }

        $usedRecoveryCode = $recoveryCode !== '';
        $valid = $usedRecoveryCode
            ? $this->twoFactor->useRecoveryCode($user, $recoveryCode)
            : $this->twoFactor->verifyUserCode($user, $code);

        if (! $valid) {
            RateLimiter::hit($key, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'code' => ['Kode salah.', 'Pastikan jam HP kamu disetel otomatis.'],
            ]);
        }

        RateLimiter::clear($key);

        $request->session()->forget('two_factor.pending');
        Auth::login($user);
        $request->session()->regenerate();

        if ($request->boolean('trust_device')) {
            $this->trustedDevices->trust($user, $request);
        }

        $user->notify(new NewLoginNotification(
            (string) $request->ip(),
            UserAgentSummary::summarize($request->userAgent()),
            $this->methodLabel($pending['method'], $usedRecoveryCode),
            now(),
        ));

        if ($usedRecoveryCode) {
            $user->notify(new RecoveryCodeUsedNotification($user->recoveryCodesRemaining()));
        }

        return redirect($pending['intended']);
    }

    /**
     * Pending yang hilang, kedaluwarsa, atau user-nya sudah tidak ada / tidak lagi ber-2FA
     * diperlakukan sama: kembali ke halaman login yang sesuai.
     *
     * @return array{0: array<string, mixed>, 1: User}|RedirectResponse
     */
    private function resolvePending(Request $request): array|RedirectResponse
    {
        $pending = $request->session()->get('two_factor.pending');
        $user = null;
        $valid = is_array($pending)
            && ($pending['expires_at'] ?? 0) >= now()->timestamp;

        if ($valid) {
            $user = User::find($pending['user_id'] ?? null);
            $valid = $user !== null && $user->hasTwoFactorEnabled();
        }

        if ($valid) {
            return [$pending, $user];
        }

        $request->session()->forget('two_factor.pending');

        if ($user === null && is_array($pending)) {
            $user = User::find($pending['user_id'] ?? null);
        }

        return redirect()
            ->route($user?->isAdmin() ? 'admin.login' : 'login')
            ->withErrors(['email' => 'Waktu habis, silakan masuk lagi.']);
    }

    private function methodLabel(string $method, bool $usedRecoveryCode): string
    {
        return ($method === 'google' ? 'Google' : 'Password')
            .($usedRecoveryCode ? ' + recovery code' : ' + kode authenticator');
    }
}
