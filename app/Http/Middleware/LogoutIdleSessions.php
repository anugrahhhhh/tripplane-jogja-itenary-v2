<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keluarkan user yang tidak aktif lebih lama dari `auth.idle_timeout`.
 * Request latar belakang (header X-Background-Request) tetap dicek,
 * tetapi tidak memperpanjang sesi.
 */
class LogoutIdleSessions
{
    public const SESSION_KEY = 'auth.last_activity_at';

    public static function message(): string
    {
        return 'Kamu keluar otomatis karena tidak aktif selama '.intdiv((int) config('auth.idle_timeout'), 60).' menit.';
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user === null) {
            return $next($request);
        }

        $session = $request->session();
        $lastActivity = $session->get(self::SESSION_KEY);

        if ($lastActivity !== null && now()->timestamp - $lastActivity > (int) config('auth.idle_timeout')) {
            $isAdmin = $user->isAdmin();

            Auth::logout();
            $session->invalidate();
            $session->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['reason' => 'idle_timeout'], 401);
            }

            return redirect()
                ->route($isAdmin ? 'admin.login' : 'login')
                ->with('status', self::message());
        }

        if ($lastActivity === null || ! $request->hasHeader('X-Background-Request')) {
            $session->put(self::SESSION_KEY, now()->timestamp);
        }

        return $next($request);
    }
}
