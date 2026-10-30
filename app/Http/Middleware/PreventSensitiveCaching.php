<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Halaman berisi data akun tidak boleh disimpan di cache browser.
 */
class PreventSensitiveCaching
{
    private const ROUTES = [
        'two-factor.*',
        'profile.*',
        'security.not-me*',
        'notifications.*',
        'admin.*',
        'password.*',
        'login',
        'register',
        'verification.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->route()?->named(...self::ROUTES)) {
            $response->headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
