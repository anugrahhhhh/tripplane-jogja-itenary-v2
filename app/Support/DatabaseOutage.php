<?php

namespace App\Support;

use Illuminate\Database\LostConnectionDetector;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use PDOException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Mengenali error "database tidak bisa dihubungi" dan membuat respons 503
 * yang ramah (halaman gangguan / JSON) dengan header Retry-After.
 */
class DatabaseOutage
{
    public const RETRY_AFTER_SECONDS = 30;

    public static function causedBy(Throwable $e): bool
    {
        for ($current = $e; $current !== null; $current = $current->getPrevious()) {
            if (! $current instanceof QueryException && ! $current instanceof PDOException) {
                continue;
            }

            if ((new LostConnectionDetector)->causedByLostConnection($current)) {
                return true;
            }

            // Server DB mati total / tidak bisa dihubungi sama sekali.
            if (preg_match('/SQLSTATE\[(HY000|08006|08001)\] \[(2002|2003|2005|2006|1045)\]|could not connect to server|Connection refused/i', $current->getMessage())) {
                return true;
            }
        }

        return false;
    }

    public static function response(Request $request): Response
    {
        $headers = [
            'Retry-After' => (string) self::RETRY_AFTER_SECONDS,
            'Cache-Control' => 'no-store',
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Layanan sedang mengalami gangguan sementara. Silakan coba lagi sebentar lagi.',
            ], 503, $headers);
        }

        return response()->view('errors.503', ['outage' => true], 503, $headers);
    }
}
