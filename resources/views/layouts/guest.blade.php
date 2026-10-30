<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'JogjaTrip') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-cream px-4">
            <a href="{{ route('home') }}" class="text-3xl font-bold text-primary">
                Jogja<span class="text-accent-500">Trip</span>
            </a>
            <p class="mt-1 text-sm text-ink/60">Trip planner destinasi wisata Yogyakarta</p>

            <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-sm border border-primary-100 overflow-hidden sm:rounded-2xl">
                {{ $slot }}
            </div>

            <a href="{{ route('home') }}" class="mt-6 text-sm text-ink/50 hover:text-primary">&larr; Kembali ke Beranda</a>
        </div>

        @auth
            @include('partials.idle-timeout')
        @endauth
    </body>
</html>
