<x-auth-split-layout activeTab="login">
    <x-slot name="aside">
        <p class="text-sm font-medium text-primary-200">Trip Planner</p>
        <h1 class="mt-6 text-3xl font-bold text-white text-balance">Rencanakan perjalananmu dengan lebih mudah.</h1>
        <p class="mt-3 text-primary-100 text-sm max-w-xs">Simpan tempat yang kamu suka, susun destinasi berdasarkan hari, dan buat itinerary perjalananmu dalam satu tempat.</p>

        <div class="mt-10 grid grid-cols-3 gap-2">
            <div class="rounded-xl bg-white/10 p-3">
                <p class="text-xs font-semibold text-white">♡ Favorite</p>
                <p class="mt-1 text-[10.5px] leading-tight text-primary-200">Tempat yang ingin kamu kunjungi</p>
            </div>
            <div class="rounded-xl bg-white/10 p-3">
                <p class="text-xs font-semibold text-white">☰ Itinerary</p>
                <p class="mt-1 text-[10.5px] leading-tight text-primary-200">Susun perjalanan berdasarkan hari</p>
            </div>
            <div class="rounded-xl bg-white/10 p-3">
                <p class="text-xs font-semibold text-white">⌕ Explore</p>
                <p class="mt-1 text-[10.5px] leading-tight text-primary-200">Temukan destinasi baru</p>
            </div>
        </div>
    </x-slot>

    <h2 class="text-2xl font-bold text-ink">Selamat datang kembali</h2>
    <p class="mt-1 text-sm text-ink/60">Masuk untuk melanjutkan perencanaanmu.</p>

    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold uppercase tracking-wide text-ink/50">Email</label>
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold uppercase tracking-wide text-ink/50">Password</label>
            <x-text-input id="password" class="block mt-1.5 w-full" type="password" name="password" required autocomplete="current-password" placeholder="Masukkan password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end">
            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-accent-500 hover:underline" href="{{ route('password.request') }}">Lupa password?</a>
            @endif
        </div>

        <button type="submit" class="w-full rounded-xl bg-primary py-3 text-sm font-semibold text-white hover:bg-primary-600 transition">
            Masuk
        </button>
    </form>

    <div class="mt-6 flex items-center gap-3">
        <div class="flex-1 h-px bg-primary-100"></div>
        <span class="text-xs text-ink/40">atau</span>
        <div class="flex-1 h-px bg-primary-100"></div>
    </div>

    <div class="mt-4">
        <x-google-button />
    </div>

    <p class="mt-6 text-center text-sm text-ink/60">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-accent-500 hover:underline">Daftar sekarang</a>
    </p>

    <p class="mt-2 text-center text-xs text-ink/40">
        <a href="{{ route('admin.login') }}" class="hover:underline">Masuk sebagai Admin</a>
    </p>

    @include('partials.clear-page-cache')
</x-auth-split-layout>
