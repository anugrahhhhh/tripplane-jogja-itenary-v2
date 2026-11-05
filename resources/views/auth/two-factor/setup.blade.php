<x-guest-layout>
    <h1 class="text-lg font-semibold text-ink">Aktifkan verifikasi dua langkah</h1>
    <p class="mt-2 mb-4 text-sm text-ink/60">
        Demi keamanan, akunmu wajib dilindungi 2FA. Pindai kode QR di bawah dengan aplikasi authenticator
        (Google Authenticator, Authy, dll.), lalu masukkan kode 6 digit yang muncul.
    </p>

    <div class="flex justify-center rounded-lg bg-white p-3 border border-ink/10">
        <div class="w-48 h-48">{!! $qrSvg !!}</div>
    </div>

    <div class="mt-4 text-sm text-ink/60">
        Tidak bisa memindai? Masukkan kunci ini secara manual:
        <div class="mt-1 font-mono text-base tracking-widest text-ink select-all">{{ $secretGroups }}</div>
    </div>

    <form method="POST" action="{{ route('two-factor.setup.store') }}" class="mt-6">
        @csrf

        <div>
            <x-input-label for="code" value="Kode 6 digit" />
            <x-text-input id="code" class="block mt-1 w-full tracking-widest" type="text" name="code"
                inputmode="numeric" autocomplete="one-time-code" maxlength="10" required autofocus />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <button type="submit" form="logout-form" class="text-sm text-ink/60 underline hover:text-ink">
                Keluar
            </button>

            <x-primary-button>
                Aktifkan
            </x-primary-button>
        </div>
    </form>

    <form id="logout-form" method="POST" action="{{ route('logout') }}" class="hidden">
        @csrf
    </form>
</x-guest-layout>
