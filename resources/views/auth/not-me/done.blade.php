<x-guest-layout>
    <div class="text-sm text-ink/70">
        <p class="text-lg font-semibold text-ink">Akun kamu sudah diamankan</p>
        <p class="mt-2">
            Akun kamu sudah diamankan. Semua sesi sudah dikeluarkan dan 2FA di-reset.
            Kami sudah mengirim link untuk membuat password baru ke email kamu.
        </p>

        @if ($googleHint)
            <div class="mt-4 rounded-md bg-amber-50 p-3 text-sm text-amber-800">
                Kamu biasa masuk dengan Google? Amankan juga akun Google-mu di myaccount.google.com/security.
            </div>
        @endif
    </div>
</x-guest-layout>
