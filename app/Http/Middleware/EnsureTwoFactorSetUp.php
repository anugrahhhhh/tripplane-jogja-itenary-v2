<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * 2FA wajib: user yang sudah masuk tapi belum mengaktifkan 2FA hanya boleh
 * mengakses alur setup, keluar, ping sesi, dan link "Bukan saya".
 */
class EnsureTwoFactorSetUp
{
    private const ALLOWED_ROUTES = [
        'two-factor.setup*',
        'two-factor.recovery-codes*',
        'logout',
        'session.ping',
        'security.not-me*',
        'itineraries.shared',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user === null || $user->hasTwoFactorEnabled() || $request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['reason' => 'two_factor_setup_required'], 403);
        }

        return redirect()->guest(route('two-factor.setup'));
    }
}
