<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Circuit breaker layanan eksternal
    |--------------------------------------------------------------------------
    |
    | Bila sebuah layanan gagal berturut-turut sebanyak "threshold" kali,
    | sirkuit "terbuka" selama "cooldown" detik: permintaan berikutnya
    | langsung memakai jalur cadangan tanpa menunggu timeout. Setelah
    | cooldown, satu permintaan percobaan dibiarkan lewat (half-open);
    | bila berhasil sirkuit menutup lagi, bila gagal terbuka lagi.
    |
    */

    'breakers' => [
        'gemini' => [
            'threshold' => (int) env('BREAKER_GEMINI_THRESHOLD', 3),
            'cooldown' => (int) env('BREAKER_GEMINI_COOLDOWN', 120),
        ],
        'weather' => [
            'threshold' => (int) env('BREAKER_WEATHER_THRESHOLD', 3),
            'cooldown' => (int) env('BREAKER_WEATHER_COOLDOWN', 300),
        ],
    ],

    /*
    | Data cuaca terakhir yang berhasil diambil disimpan lebih lama dan
    | dipakai bila Open-Meteo gangguan (stale-if-error).
    */
    'weather_stale_hours' => (int) env('WEATHER_STALE_HOURS', 72),

    /*
    | Percobaan ulang transaksi database bila terjadi deadlock / koneksi
    | terputus sesaat.
    */
    'db_transaction_attempts' => (int) env('DB_TRANSACTION_ATTEMPTS', 3),

    /*
    | Peringatan otomatis saat status /health berubah (ok -> degraded/down
    | dan saat pulih). Dikirim ke alamat ini; kosong = hanya dicatat di log
    | dan notifikasi admin di aplikasi.
    */
    'alert_email' => env('HEALTH_ALERT_EMAIL'),

    // Jeda minimal antar peringatan untuk status yang sama (menit).
    'alert_cooldown_minutes' => (int) env('HEALTH_ALERT_COOLDOWN', 30),

];
