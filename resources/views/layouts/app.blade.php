<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' - '.config('app.name') : config('app.name') }}</title>

        <link rel="manifest" href="/manifest.json">
        <meta name="theme-color" content="#2F6B5A">
        <link rel="icon" href="/icons/icon-192.png">
        <link rel="apple-touch-icon" href="/icons/apple-touch-icon.png">

        @include('partials.fonts')

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{ $head ?? '' }}

        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register('/sw.js').catch(() => {});
                });
            }
        </script>
    </head>
    <body class="font-sans antialiased bg-cream text-ink">
        <a href="#konten-utama" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded-xl focus:bg-primary-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lift focus:outline-none focus:ring-2 focus:ring-accent-400">Lewati ke konten utama</a>
        <div class="min-h-screen flex flex-col">
            @include('layouts.navigation')

            @include('partials.flash')

            @auth
                @include('partials.recovery-codes-banner')
            @endauth

            @isset($header)
                <header class="relative z-10 bg-gradient-to-br from-primary-50 via-cream to-accent-50 border-b border-primary-100/70">
                    <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
                        <div class="absolute inset-0 pattern-dots-dark opacity-60"></div>
                        <div class="absolute -top-24 -right-16 w-72 h-72 rounded-full bg-accent-200/40 blur-3xl"></div>
                    </div>
                    <div class="relative max-w-7xl mx-auto py-8 sm:py-10 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main id="konten-utama" tabindex="-1" class="flex-1 focus:outline-none">
                {{ $slot }}
            </main>

            @include('layouts.footer')
        </div>

        @if (session('clear_page_cache'))
            @include('partials.clear-page-cache')
        @endif

        @auth
            @include('partials.idle-timeout')
        @endauth
    </body>
</html>
