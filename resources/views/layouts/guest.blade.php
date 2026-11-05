<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#2F6B5A">

        <title>{{ config('app.name', 'JogjaTrip') }}</title>
        <link rel="icon" href="/icons/icon-192.png">

        @include('partials.fonts')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased bg-cream">
        <div class="relative min-h-screen flex flex-col justify-center items-center py-10 px-4 overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 pattern-dots-dark opacity-70"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -top-40 -left-32 w-[30rem] h-[30rem] rounded-full bg-primary-200/40 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-40 -right-32 w-[30rem] h-[30rem] rounded-full bg-accent-200/50 blur-3xl"></div>

            <a href="{{ route('home') }}" class="relative" aria-label="JogjaTrip — Beranda">
                <x-brand size="lg" />
            </a>
            <p class="relative mt-2 text-sm text-ink/60">Trip planner destinasi wisata Yogyakarta</p>

            <div class="relative w-full sm:max-w-md mt-8 px-6 py-8 sm:px-8 bg-white shadow-lift ring-1 ring-primary-100/70 rounded-3xl">
                {{ $slot }}
            </div>

            <a href="{{ route('home') }}" class="relative mt-6 text-sm text-ink/50 hover:text-primary-700">&larr; Kembali ke Beranda</a>
        </div>

        @if (session('clear_page_cache'))
            @include('partials.clear-page-cache')
        @endif

        @auth
            @include('partials.idle-timeout')
        @endauth
    </body>
</html>
