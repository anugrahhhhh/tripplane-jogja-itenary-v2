<?php

namespace App\Http\Controllers;

use App\Services\SystemHealth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Readiness check untuk load balancer / uptime monitor.
 *
 * /up (bawaan Laravel) hanya memastikan PHP hidup. /health memeriksa juga
 * database, cache, storage, antrean, scheduler, dan backup. Kode 503 hanya
 * dikirim bila komponen inti mati, supaya platform tidak me-restart
 * aplikasi hanya karena backup terlambat.
 *
 * Route ini sengaja di luar grup "web" (tanpa sesi/cookie/CSRF): sesi
 * disimpan di database, jadi kalau database mati endpoint ini tetap bisa
 * menjawab 503 dengan JSON, bukan error 500.
 *
 * Detail per komponen hanya untuk pemegang HEALTH_TOKEN. Admin melihat
 * detailnya di dashboard admin.
 */
class HealthController extends Controller
{
    public function __invoke(Request $request, SystemHealth $health): JsonResponse
    {
        $report = $health->report();
        $code = $report['status'] === 'down' ? 503 : 200;

        $body = $this->canSeeDetails($request)
            ? $report
            : ['status' => $report['status'], 'checked_at' => $report['checked_at']];

        return response()->json($body, $code)->header('Cache-Control', 'no-store');
    }

    private function canSeeDetails(Request $request): bool
    {
        $token = (string) config('app.health_token');
        $given = (string) ($request->header('X-Health-Token') ?? $request->query('token', ''));

        return $token !== '' && $given !== '' && hash_equals($token, $given);
    }
}
