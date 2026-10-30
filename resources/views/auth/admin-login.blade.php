<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Admin — {{ config('app.name', 'JogjaTrip') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-primary-900 px-4">
            <a href="{{ route('home') }}" class="text-3xl font-bold text-white">
                Jogja<span class="text-accent-400">Trip</span>
            </a>
            <p class="mt-1 text-sm text-primary-200 tracking-wide uppercase">Panel Admin</p>

            <div class="w-full sm:max-w-md mt-6 px-6 py-8 bg-white shadow-xl overflow-hidden sm:rounded-2xl">
                <h2 class="text-xl font-bold text-ink">Masuk sebagai Admin</h2>
                <p class="mt-1 text-sm text-ink/60">Halaman ini khusus untuk pengelola JogjaTrip.</p>

                <x-auth-session-status class="mt-4" :status="session('status')" />

                <form method="POST" action="{{ route('admin.login') }}" class="mt-6 space-y-4">
                    @csrf

                    <div>
                        <label for="email" class="block text-xs font-semibold uppercase tracking-wide text-ink/50">Email Admin</label>
                        <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="admin@jogjatrip.test" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="block text-xs font-semibold uppercase tracking-wide text-ink/50">Password</label>
                        <x-text-input id="password" class="block mt-1.5 w-full" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password" />
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <button type="submit" class="w-full rounded-xl bg-primary-900 py-3 text-sm font-semibold text-white hover:bg-ink transition">
                        Masuk ke Panel Admin
                    </button>
                </form>
            </div>

            <a href="{{ route('login') }}" class="mt-6 text-sm text-primary-200 hover:text-white">Bukan Admin? Masuk sebagai wisatawan &rarr;</a>
        </div>

        @include('partials.clear-page-cache')
    </body>
</html>
