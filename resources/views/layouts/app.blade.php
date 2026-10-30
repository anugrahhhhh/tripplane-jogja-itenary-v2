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

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

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
        <div class="min-h-screen flex flex-col">
            @include('layouts.navigation')

            @if (session('status'))
                <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4">
                    <div class="rounded-lg bg-primary-50 border border-primary-200 text-primary-800 px-4 py-3 text-sm">
                        {{ session('status') }}
                    </div>
                </div>
            @endif

            @auth
                @include('partials.recovery-codes-banner')
            @endauth

            @isset($header)
                <header class="bg-white border-b border-primary-100">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="flex-1">
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
