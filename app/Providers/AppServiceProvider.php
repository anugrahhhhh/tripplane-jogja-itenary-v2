<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Destination;
use App\Models\DestinationPhoto;
use App\Models\Review;
use App\Services\SystemHealth;
use App\Support\CatalogCache;
use App\Support\OutboundUrl;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Data katalog di-cache (CatalogCache); kosongkan setiap ada perubahan
        // supaya beranda dan katalog langsung menampilkan data terbaru.
        foreach ([Category::class, Destination::class, DestinationPhoto::class, Review::class] as $model) {
            $model::saved(fn () => CatalogCache::flush());
            $model::deleted(fn () => CatalogCache::flush());
        }

        $this->configureRateLimiting();
        $this->configureOutboundEmailUrls();
        $this->configurePasswordRules();

        // Detak jantung worker queue untuk /health (dibatasi 1x per 30 detik).
        Queue::looping(fn () => SystemHealth::beatQueueWorker());
    }

    /**
     * Link reset password & verifikasi email selalu memakai APP_URL, bukan
     * header Host dari request (mencegah password reset poisoning).
     */
    protected function configureOutboundEmailUrls(): void
    {
        ResetPassword::createUrlUsing(fn (object $notifiable, string $token) => OutboundUrl::using(
            fn () => route('password.reset', [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ])
        ));

        VerifyEmail::createUrlUsing(fn (object $notifiable) => OutboundUrl::using(
            fn () => URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes((int) config('auth.verification.expire', 60)),
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            )
        ));
    }

    /**
     * Aturan kata sandi untuk daftar, reset, dan ganti kata sandi: minimal
     * 8 karakter, berisi huruf dan angka. Sengaja tanpa uncompromised()
     * karena itu memanggil API luar di setiap pendaftaran (menambah titik
     * gagal dan waktu tunggu).
     */
    protected function configurePasswordRules(): void
    {
        Password::defaults(fn () => Password::min(8)->max(255)->letters()->numbers());
    }

    /**
     * Batas percobaan per IP untuk form tamu yang belum dibatasi bawaan
     * framework. Batasnya cukup longgar agar satu kelas yang memakai Wi-Fi
     * kampus yang sama tetap bisa mendaftar bersamaan, tapi cukup ketat untuk
     * menahan bot yang membuat akun palsu atau membanjiri email reset.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('register', fn (Request $request) => Limit::perMinute(10)
            ->by('register|'.$request->ip())
            ->response(fn (Request $request, array $headers) => $this->tooManyAttempts($request, $headers)));

        RateLimiter::for('password-email', fn (Request $request) => Limit::perMinute(5)
            ->by('password-email|'.$request->ip())
            ->response(fn (Request $request, array $headers) => $this->tooManyAttempts($request, $headers)));

        RateLimiter::for('password-reset', fn (Request $request) => Limit::perMinute(10)
            ->by('password-reset|'.$request->ip())
            ->response(fn (Request $request, array $headers) => $this->tooManyAttempts($request, $headers)));
    }

    /**
     * Kembalikan pengguna ke form dengan pesan yang jelas (bukan halaman 429),
     * tanpa mengisi ulang kolom kata sandi.
     */
    protected function tooManyAttempts(Request $request, array $headers): RedirectResponse
    {
        $seconds = max(1, (int) ($headers['Retry-After'] ?? 60));

        return back()
            ->withInput($request->except(['password', 'password_confirmation']))
            ->withErrors(['email' => 'Terlalu banyak percobaan. Coba lagi dalam '.$seconds.' detik.']);
    }
}
