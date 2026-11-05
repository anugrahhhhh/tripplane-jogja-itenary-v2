<x-auth-split-layout activeTab="register">
    <x-slot name="aside">
        <span class="inline-flex items-center gap-2 rounded-full bg-white/10 ring-1 ring-white/15 px-3 py-1 text-xs font-semibold text-primary-100">
            <span class="w-1.5 h-1.5 rounded-full bg-accent-300"></span>
            Trip Planner
        </span>
        <h1 class="mt-6 text-3xl sm:text-4xl font-extrabold text-white text-balance leading-tight">Mulai susun perjalanan impianmu.</h1>
        <p class="mt-3 text-primary-100/80 text-sm max-w-sm">Gabungkan destinasi favoritmu dalam satu tempat. Simpan tempat yang kamu suka dan susun perjalanan berdasarkan hari dengan lebih teratur.</p>

        @include('partials.auth-features', ['favorite' => 'Simpan tempat favoritmu', 'itinerary' => 'Atur perjalananmu', 'explore' => 'Temukan tempat baru'])
    </x-slot>

    <h2 class="text-2xl sm:text-3xl font-extrabold text-ink">Buat akun baru</h2>
    <p class="mt-1.5 text-sm text-ink/60">Daftar untuk mulai menyimpan destinasi dan membuat itinerary perjalananmu.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6 space-y-4" x-data="{ loading: false }" @submit="loading = true">
        @csrf

        <div>
            <label for="name" class="label">Nama Lengkap</label>
            <x-text-input id="name" class="block mt-1.5 w-full py-3" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Masukkan nama lengkap" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <label for="email" class="label">Email</label>
            <x-text-input id="email" class="block mt-1.5 w-full py-3" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="nama@email.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="password" class="label">Password</label>
                <div class="mt-1.5">
                    <x-password-input id="password" class="block w-full py-3" name="password" required minlength="8" autocomplete="new-password" placeholder="Minimal 8 karakter" aria-describedby="password-hint" />
                </div>
                <p id="password-hint" class="mt-1.5 text-xs text-ink/50">Minimal 8 karakter, berisi huruf dan angka.</p>
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <label for="password_confirmation" class="label">Konfirmasi Password</label>
                <div class="mt-1.5">
                    <x-password-input id="password_confirmation" class="block w-full py-3" name="password_confirmation" required autocomplete="new-password" placeholder="Ulangi password" />
                </div>
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
        </div>
        <p class="-mt-2 text-xs text-ink/40">Gunakan minimal 8 karakter agar akunmu lebih aman.</p>

        <label class="flex items-start gap-2.5 text-sm text-ink/60 cursor-pointer">
            <input type="checkbox" required class="mt-0.5 rounded border-ink/20 text-primary focus:ring-primary">
            <span>Saya menyetujui <strong class="text-ink">syarat &amp; ketentuan</strong> JogjaTrip.</span>
        </label>

        <button type="submit" :disabled="loading" class="btn-primary w-full py-3.5">
            <svg x-show="loading" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
            Buat Akun
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
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-semibold text-accent-500 hover:underline">Masuk sekarang</a>
    </p>
</x-auth-split-layout>
