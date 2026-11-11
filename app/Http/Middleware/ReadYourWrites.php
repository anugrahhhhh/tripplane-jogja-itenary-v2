<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

/**
 * Konsistensi "read-your-writes" saat memakai replika baca (DB_READ_HOST).
 *
 * Opsi "sticky" Laravel hanya berlaku di dalam SATU request. Padahal pola
 * umum aplikasi web adalah simpan -> redirect -> tampilkan. Bila request
 * kedua membaca dari replika yang tertinggal beberapa milidetik, data yang
 * baru disimpan bisa tampak hilang (bahkan 404).
 *
 * Middleware ini: setelah sebuah request menulis ke database, request
 * berikutnya dari pengguna yang sama selama beberapa detik dibaca dari
 * primary. Tanpa replika, middleware ini tidak melakukan apa pun.
 */
class ReadYourWrites
{
    public const SESSION_KEY = 'db.read_primary_until';

    public const WINDOW_SECONDS = 5;

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->usesReadReplica() || ! $request->hasSession()) {
            return $next($request);
        }

        $connection = DB::connection();

        if ((int) $request->session()->get(self::SESSION_KEY, 0) >= now()->timestamp) {
            $connection->useWriteConnectionWhenReading();
        }

        $response = $next($request);

        if ($connection->hasModifiedRecords()) {
            $request->session()->put(self::SESSION_KEY, now()->timestamp + self::WINDOW_SECONDS);
        }

        return $response;
    }

    private function usesReadReplica(): bool
    {
        return ! empty(config('database.connections.'.config('database.default').'.read'));
    }
}
