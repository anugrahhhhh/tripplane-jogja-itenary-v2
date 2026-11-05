<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0F241F">

        <title>Admin — {{ config('app.name', 'JogjaTrip') }}</title>
        <link rel="icon" href="/icons/icon-192.png">

        @include('partials.fonts')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased bg-primary-900">
        <div class="relative min-h-screen flex flex-col justify-center items-center py-10 px-4 overflow-hidden">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 pattern-dots opacity-60"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -top-40 -left-32 w-[32rem] h-[32rem] rounded-full bg-primary-500/30 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-40 -right-32 w-[32rem] h-[32rem] rounded-full bg-accent-500/20 blur-3xl"></div>

            <a href="{{ route('home') }}" class="relative" aria-label="JogjaTrip — Beranda">
                <x-brand dark size="lg" />
            </a>
            <p class="relative mt-3 inline-flex items-center gap-1.5 rounded-full bg-white/10 ring-1 ring-white/15 px-3 py-1 text-xs font-bold text-primary-100 tracking-[0.18em] uppercase">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
                Panel Admin
            </p>

            <div class="relative w-full sm:max-w-md mt-8 px-6 py-8 sm:px-8 bg-white shadow-2xl rounded-3xl">
                <h2 class="text-2xl font-extrabold text-ink">Masuk sebagai Admin</h2>
                <p class="mt-1 text-sm text-ink/60">Halaman ini khusus untuk pengelola JogjaTrip.</p>

                <x-auth-session-status class="mt-4" :status="session('status')" />

                <form method="POST" action="{{ route('admin.login') }}" class="mt-6 space-y-4" x-data="{ loading: false }" @submit="loading = true">
                    @csrf

                    <div>
                        <label for="email" class="label">Email Admin</label>
                        <x-text-input id="email" class="block mt-1.5 w-full py-3" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="admin@jogjatrip.test" />
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="label">Password</label>
                        <div class="mt-1.5">
                            <x-password-input id="password" class="block w-full py-3" name="password" required autocomplete="current-password" placeholder="Masukkan password" />
                        </div>
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <button type="submit" :disabled="loading" class="btn w-full py-3.5 bg-primary-900 text-white hover:bg-primary-800 focus-visible:ring-primary-500/30">
                        <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        Masuk ke Panel Admin
                    </button>
                </form>
            </div>

            <a href="{{ route('login') }}" class="relative mt-6 text-sm text-primary-200 hover:text-white">Bukan Admin? Masuk sebagai wisatawan &rarr;</a>
        </div>

        @include('partials.clear-page-cache')
    </body>
</html>
