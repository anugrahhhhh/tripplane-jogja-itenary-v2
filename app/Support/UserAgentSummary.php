<?php

namespace App\Support;

class UserAgentSummary
{
    /**
     * Ringkas user agent menjadi "{Browser} di {OS}".
     */
    public static function summarize(?string $userAgent): string
    {
        if ($userAgent === null || trim($userAgent) === '') {
            return 'Browser tidak dikenal';
        }

        // Urutan penting: Edge/Opera/Chrome juga mengandung "Safari/".
        $browser = match (true) {
            str_contains($userAgent, 'Edg/') => 'Edge',
            str_contains($userAgent, 'OPR/') => 'Opera',
            str_contains($userAgent, 'Firefox/') => 'Firefox',
            str_contains($userAgent, 'Chrome/') => 'Chrome',
            str_contains($userAgent, 'Safari/') => 'Safari',
            default => null,
        };

        if ($browser === null) {
            return 'Browser tidak dikenal';
        }

        // Android mengandung "Linux" dan iOS mengandung "Mac OS X", jadi cek lebih dulu.
        $os = match (true) {
            str_contains($userAgent, 'Windows') => 'Windows',
            str_contains($userAgent, 'Android') => 'Android',
            (bool) preg_match('/iPhone|iPad/', $userAgent) => 'iOS',
            str_contains($userAgent, 'Mac OS X') => 'macOS',
            str_contains($userAgent, 'Linux') => 'Linux',
            default => null,
        };

        return $os === null ? $browser : $browser.' di '.$os;
    }
}
