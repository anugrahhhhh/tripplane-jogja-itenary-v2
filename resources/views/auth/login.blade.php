<x-auth-split-layout activeTab="login">
    <x-slot name="aside">
        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 ring-1 ring-white/15 px-3 py-1 text-xs font-semibold text-primary-100">
            <span class="w-1.5 h-1.5 rounded-full bg-accent-300"></span>
            Trip Planner
        </span>
        <h1 class="mt-6 text-3xl sm:text-4xl font-extrabold text-white text-balance leading-tight">Rencanakan perjalananmu dengan lebih mudah.</h1>
        <p class="mt-3 text-primary-100/80 text-sm max-w-sm">Simpan tempat yang kamu suka, susun destinasi berdasarkan hari, dan buat itinerary perjalananmu dalam satu tempat.</p>

        @include('partials.auth-features')
    </x-slot>

    <h2 class="text-2xl sm:text-3xl font-extrabold text-ink">Selamat datang kembali 👋</h2>
    <p class="mt-1.5 text-sm text-ink/60">Masuk untuk melanjutkan perencanaanmu.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <div>
            <label for="email" class="label">Email</label>
            <x-text-input id="email" class="block mt-1.5 w-full py-3" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <label for="password" class="label">Password</label>
                @if (Route::has('password.request'))
                    <a class="text-xs font-semibold text-accent-500 hover:underline" href="{{ route('password.request') }}">Lupa password?</a>
                @endif
            </div>
            <div class="mt-1.5">
                <x-password-input id="password" class="block w-full py-3" name="password" required autocomplete="current-password" placeholder="Masukkan password" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit" :disabled="loading" class="btn-primary w-full py-3.5">
            <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
            Masuk
        </button>
    </form>

    <div class="mt-6 flex items-center gap-3">
        <div class="flex-1 h-px bg-primary-100"></div>
        <span class="text-xs font-medium text-ink/40">atau</span>
        <div class="flex-1 h-px bg-primary-100"></div>
    </div>

    <div class="mt-4">
        <x-google-button />
    </div>

    <p class="mt-6 text-center text-sm text-ink/60">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-accent-500 hover:underline">Daftar sekarang</a>
    </p>

    <p class="mt-3 text-center text-xs text-ink/40">
        <a href="{{ route('admin.login') }}" class="inline-flex items-center gap-1 hover:text-primary-700 hover:underline">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
            Masuk sebagai Admin
        </a>
    </p>

    @include('partials.clear-page-cache')
</x-auth-split-layout>
