@php($authUser = auth()->user())
@if ($authUser && $authUser->hasTwoFactorEnabled() && $authUser->recoveryCodesRemaining() <= 2)
    <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4">
        <div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 text-sm">
            <strong>Recovery code hampir habis</strong>
            (sisa {{ $authUser->recoveryCodesRemaining() }}).
            <a href="{{ route('profile.edit') }}" class="font-semibold underline">Buat recovery code baru</a>
            agar tetap bisa masuk jika kehilangan aplikasi autentikator.
        </div>
    </div>
@endif
