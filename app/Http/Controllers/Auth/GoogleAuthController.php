<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\TwoFactorGate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleAuthController extends Controller
{
    /**
     * Arahkan ke halaman izin Google.
     */
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Tangani balasan dari Google: cocokkan ke akun yang sudah ada lewat
     * google_id atau email, atau buat akun traveler baru. Akun Admin tidak
     * pernah dibuat/ditautkan lewat jalur ini — konsisten dengan pemisahan
     * pintu masuk admin vs wisatawan yang sudah ada.
     */
    public function callback(Request $request, TwoFactorGate $gate): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable $e) {
            return redirect()->route('login')->withErrors([
                'email' => 'Gagal masuk dengan Google. Silakan coba lagi.',
            ]);
        }

        $user = User::where('google_id', $googleUser->getId())->first()
            ?? User::where('email', $googleUser->getEmail())->first();

        if ($user && $user->isAdmin()) {
            return redirect()->route('login')->withErrors([
                'email' => 'Akun ini adalah akun Admin. Silakan masuk lewat halaman khusus Admin.',
            ]);
        }

        if ($user) {
            if (! $user->google_id) {
                $user->forceFill(['google_id' => $googleUser->getId()])->save();
            }
        } else {
            $user = User::create([
                'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Traveler'),
                'email' => $googleUser->getEmail(),
                'password' => Str::random(40),
                'role' => 'traveler',
                'google_id' => $googleUser->getId(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            // email_verified_at sengaja tidak ada di $fillable User (supaya
            // tidak bisa diisi lewat form biasa) — di sini aman di-set
            // manual karena email sudah terverifikasi oleh Google sendiri.
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return $gate->pass($request, $user, 'google', $request->session()->pull('url.intended', route('home')));
    }
}
