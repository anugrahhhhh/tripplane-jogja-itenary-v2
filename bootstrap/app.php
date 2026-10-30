<?php

use App\Http\Middleware\EnsureTwoFactorSetUp;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\LogoutIdleSessions;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
        ]);

        // Daftar urut: sesi idle dikeluarkan dulu sebelum redirect setup 2FA
        $middleware->web(append: [
            LogoutIdleSessions::class,
            EnsureTwoFactorSetUp::class,
        ]);

        // Tamu yang belum masuk dan membuka halaman /admin/* diarahkan ke
        // pintu masuk Admin, bukan halaman masuk wisatawan, agar pemisahan
        // akses tetap konsisten walau lewat akses langsung ke URL.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*')
            ? route('admin.login')
            : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
