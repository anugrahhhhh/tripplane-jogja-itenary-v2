@php($authUser = auth()->user())
@if ($authUser && $authUser->hasTwoFactorEnabled() && $authUser->recoveryCodesRemaining() <= 2)
    <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4">
        <div class="alert-warning shadow-soft">
            <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <p>
                <strong>Recovery code hampir habis</strong>
                (sisa {{ $authUser->recoveryCodesRemaining() }}).
                <a href="{{ route('profile.edit') }}" class="font-semibold underline">Buat recovery code baru</a>
                agar tetap bisa masuk jika kehilangan aplikasi autentikator.
            </p>
        </div>
    </div>
@endif
