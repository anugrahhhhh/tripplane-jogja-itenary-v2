<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-4">
            <span class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-400 to-primary-600 text-white flex items-center justify-center text-2xl font-display font-extrabold shadow-glow">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </span>
            <div class="min-w-0">
                <h1 class="text-3xl font-extrabold text-ink">Profil Saya</h1>
                <p class="text-sm text-ink/60 mt-1">Kelola informasi akun dan keamanan akunmu.</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-1 lg:grid-cols-4 gap-8">
        <aside class="lg:col-span-1">
            <nav class="lg:sticky lg:top-24 card p-2 flex lg:flex-col gap-1 overflow-x-auto scrollbar-none" aria-label="Bagian profil">
                @foreach ([
                    ['#informasi', 'Informasi Profil', 'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'],
                    ['#kata-sandi', 'Kata Sandi', 'M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z'],
                    ['#keamanan', 'Keamanan', 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z'],
                    ['#hapus-akun', 'Hapus Akun', 'M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0'],
                ] as [$href, $label, $icon])
                    <a href="{{ $href }}" class="shrink-0 flex items-center gap-2.5 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $href === '#hapus-akun' ? 'text-red-600 hover:bg-red-50' : 'text-ink/70 hover:bg-primary-50 hover:text-primary-700' }}">
                        <svg class="w-4 h-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>
        </aside>

        <div class="lg:col-span-3 space-y-6">
            <div id="informasi" class="scroll-mt-28 card p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div id="kata-sandi" class="scroll-mt-28 card p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div id="keamanan" class="scroll-mt-28 card p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.two-factor-security')
                </div>
            </div>

            <div id="hapus-akun" class="scroll-mt-28 bg-white rounded-2xl border border-red-100 shadow-soft p-6 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
