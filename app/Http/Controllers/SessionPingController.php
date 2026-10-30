<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SessionPingController extends Controller
{
    /**
     * Middleware LogoutIdleSessions sudah memperbarui aktivitas sesi;
     * endpoint ini hanya perlu menjawab 204.
     */
    public function __invoke(): Response
    {
        return response()->noContent();
    }
}
