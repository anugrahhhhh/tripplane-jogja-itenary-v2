<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Header keamanan standar untuk semua respons aplikasi.
 *
 * CSP sengaja dibuat longgar untuk script/style (Alpine.js, peta, dan script
 * inline di view masih dipakai) dan hanya mengunci hal yang aman dikunci:
 * halaman tidak bisa di-embed situs lain, <base> tidak bisa dibajak, dan
 * plugin (<object>/<embed>) dimatikan.
 */
class SecurityHeaders
{
    private const HEADERS = [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'geolocation=(self), camera=(), microphone=(), payment=(), usb=()',
        'Content-Security-Policy' => "frame-ancestors 'self'; base-uri 'self'; object-src 'none'",
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        foreach (self::HEADERS as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        // HSTS hanya berarti lewat HTTPS. Di belakang proxy (Railway) request
        // ke PHP berupa HTTP, jadi pakai APP_URL sebagai penanda situs HTTPS.
        if ($request->isSecure() || str_starts_with((string) config('app.url'), 'https://')) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
