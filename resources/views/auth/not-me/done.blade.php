<x-guest-layout>
    <div class="text-sm text-ink/70">
        <p class="text-2xl font-extrabold text-ink">Akun kamu sudah diamankan</p>
        <p class="mt-2">
            Akun kamu sudah diamankan. Semua sesi sudah dikeluarkan dan 2FA di-reset.
            Kami sudah mengirim link untuk membuat password baru ke email kamu.
        </p>

        @if ($googleHint)
            <div class="mt-4 alert-warning">
                Kamu biasa masuk dengan Google? Amankan juga akun Google-mu di myaccount.google.com/security.
            </div>
        @endif
    </div>
    @include('partials.clear-page-cache')
</x-guest-layout>
