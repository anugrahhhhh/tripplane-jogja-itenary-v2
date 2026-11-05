<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-3.6-flash'),
        // Dicoba berurutan kalau model utama sibuk (503/429/model
        // dihentikan). Sudah diverifikasi langsung ke API (bukan tebakan)
        // bahwa kedua model ini aktif untuk akun ini dan berasal dari
        // keluarga model berbeda dari model utama, jadi kemungkinan besar
        // kapasitas servernya terpisah saat model utama sedang padat.
        'fallback_models' => env('GEMINI_FALLBACK_MODELS', 'gemini-3.5-flash,gemini-3.1-flash-lite'),
        // Batas waktu total (detik) untuk semua percobaan AI, termasuk model
        // cadangan. Pengguna tidak akan menunggu lebih lama dari ini.
        // Dijaga otomatis di rentang 10–150 detik (di bawah batas Nginx 180).
        'time_budget' => (int) env('GEMINI_TIME_BUDGET', 50),
        // Batas waktu satu percobaan ke satu model (detik).
        'attempt_timeout' => (int) env('GEMINI_ATTEMPT_TIMEOUT', 20),
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

];
