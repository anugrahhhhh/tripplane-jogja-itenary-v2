<?php

use App\Http\Controllers\HealthController;
use App\Http\Middleware\EnsureTwoFactorSetUp;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\LogoutIdleSessions;
use App\Http\Middleware\PreventSensitiveCaching;
use App\Http\Middleware\ReadYourWrites;
use App\Http\Middleware\SecurityHeaders;
use App\Support\DatabaseOutage;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            // Readiness check lengkap, di luar grup "web" (lihat HealthController).
            Route::get('/health', HealthController::class)->name('health');
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // Global (bukan hanya grup web) supaya halaman 404 untuk URL yang
        // tidak ada dan endpoint /up juga mendapat header keamanan.
        $middleware->append(SecurityHeaders::class);

        // Daftar urut: sesi idle dikeluarkan dulu sebelum redirect setup 2FA
        $middleware->web(append: [
            ReadYourWrites::class,
            LogoutIdleSessions::class,
            EnsureTwoFactorSetUp::class,
            PreventSensitiveCaching::class,
        ]);

        // Logout paksa (idle) dari beberapa tab sekaligus: tab kedua membawa token
        // CSRF dari sesi yang sudah dihapus dan akan kena 419. Dampak logout paksa kecil.
        $middleware->validateCsrfTokens(except: ['logout']);

        // Tamu yang belum masuk dan membuka halaman /admin/* diarahkan ke
        // pintu masuk Admin, bukan halaman masuk wisatawan, agar pemisahan
        // akses tetap konsisten walau lewat akses langsung ke URL.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Resilience: database tidak bisa dihubungi -> 503 "sedang gangguan"
        // dengan Retry-After (browser/monitor tahu ini sementara), bukan 500.
        $exceptions->render(function (Throwable $e, Request $request) {
            if (! DatabaseOutage::causedBy($e)) {
                return null;
            }

            return DatabaseOutage::response($request);
        });

        // Saat gangguan, ribuan request gagal dengan error yang sama. Batasi
        // laporan supaya log tidak banjir dan disk tidak penuh.
        $exceptions->dontReportDuplicates();
        $exceptions->throttle(fn (Throwable $e) => DatabaseOutage::causedBy($e)
            ? Limit::perMinute(5)->by('db-outage')
            : null);
    })->create();
