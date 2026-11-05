@props(['activeTab'])

<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#2F6B5A">

        <title>{{ ($activeTab === 'register' ? 'Daftar' : 'Masuk').' - '.config('app.name', 'JogjaTrip') }}</title>
        <link rel="icon" href="/icons/icon-192.png">

        @include('partials.fonts')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased bg-cream" x-data>
        <div class="relative min-h-screen flex flex-col items-center justify-center px-4 py-10 gap-6 overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 pattern-dots-dark opacity-70"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -top-40 -left-32 w-[32rem] h-[32rem] rounded-full bg-primary-200/40 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-40 -right-32 w-[32rem] h-[32rem] rounded-full bg-accent-200/50 blur-3xl"></div>

            <a href="{{ route('home') }}" class="relative" aria-label="JogjaTrip — Beranda">
                <x-brand size="lg" />
            </a>

            <div class="relative w-full max-w-5xl bg-white rounded-[2rem] shadow-lift ring-1 ring-primary-100/70 overflow-hidden grid grid-cols-1 lg:grid-cols-2">
                <div class="relative bg-primary-900 text-white p-8 sm:p-12 flex flex-col justify-between overflow-hidden order-2 lg:order-1 min-h-[300px]">
                    <div aria-hidden="true" class="absolute inset-0 pattern-dots opacity-70 pointer-events-none"></div>
                    <div aria-hidden="true" class="absolute -top-20 -right-20 w-72 h-72 rounded-full bg-primary-500/40 blur-2xl pointer-events-none"></div>
                    <div aria-hidden="true" class="absolute -bottom-24 -left-16 w-72 h-72 rounded-full bg-accent-500/25 blur-2xl pointer-events-none"></div>
                    <div class="relative">{{ $aside }}</div>
                    <p class="relative mt-10 text-xs text-primary-200/60">&copy; {{ date('Y') }} JogjaTrip &middot; Trip planner Yogyakarta</p>
                </div>

                <div class="p-8 sm:p-12 order-1 lg:order-2">
                    <div class="inline-flex rounded-full bg-cream ring-1 ring-primary-100 p-1 text-sm font-semibold">
                        <a href="{{ route('login') }}" class="rounded-full px-5 py-2 transition {{ $activeTab === 'login' ? 'bg-white shadow-soft text-primary-700' : 'text-ink/40 hover:text-ink' }}">Masuk</a>
                        <a href="{{ route('register') }}" class="rounded-full px-5 py-2 transition {{ $activeTab === 'register' ? 'bg-white shadow-soft text-primary-700' : 'text-ink/40 hover:text-ink' }}">Daftar</a>
                    </div>

                    <div class="mt-8">
                        {{ $slot }}
                    </div>
                </div>
            </div>

            <a href="{{ route('home') }}" class="relative text-sm text-ink/50 hover:text-primary-700">&larr; Kembali ke Beranda</a>
        </div>
    </body>
</html>
