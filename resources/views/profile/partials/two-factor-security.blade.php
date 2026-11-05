@php
    $securityUser = auth()->user();
    $trustedDevices = $securityUser->trustedDevices()->orderByDesc('last_used_at')->orderByDesc('id')->get();
    $formatDate = fn ($date) => $date?->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i').' WIB';
@endphp

<section>
    <header>
        <h2 class="text-lg font-bold text-ink">Keamanan</h2>
        <p class="mt-1 text-sm text-ink/60">Kelola verifikasi dua langkah, recovery code, dan perangkat tepercaya.</p>
    </header>

    @if (session('security_status') === 'perangkat-dicabut')
        <p class="mt-4 alert-success">Perangkat berhasil dicabut.</p>
    @elseif (session('security_status') === 'semua-perangkat-dicabut')
        <p class="mt-4 alert-success">Semua perangkat tepercaya berhasil dicabut.</p>
    @endif

    <div class="mt-6 space-y-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div class="rounded-xl bg-cream/70 ring-1 ring-primary-100 p-4">
                <p class="text-xs font-semibold text-ink/50">Verifikasi dua langkah:</p>
                <p class="mt-1 inline-flex items-center gap-1.5 text-sm font-bold {{ $securityUser->hasTwoFactorEnabled() ? 'text-primary-700' : 'text-amber-600' }}">
                    <span class="w-2 h-2 rounded-full {{ $securityUser->hasTwoFactorEnabled() ? 'bg-primary-500' : 'bg-amber-500' }}"></span>
                    {{ $securityUser->hasTwoFactorEnabled() ? 'Aktif' : 'Belum aktif' }}
                </p>
            </div>
            <div class="rounded-xl bg-cream/70 ring-1 ring-primary-100 p-4">
                <p class="text-xs font-semibold text-ink/50">Recovery code tersisa:</p>
                <p class="mt-1 text-sm font-bold text-ink">{{ $securityUser->recoveryCodesRemaining() }} dari 8</p>
            </div>
        </div>

        <form method="post" action="{{ route('profile.recovery-codes.regenerate') }}" class="space-y-3 rounded-xl ring-1 ring-primary-100 p-4">
            @csrf
            <div>
                <x-input-label for="regenerate_code" value="Kode dari aplikasi autentikator" />
                <x-text-input id="regenerate_code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" class="mt-1.5 block w-full tracking-widest font-mono" placeholder="123456" required />
                <x-input-error :messages="$errors->twoFactor->get('code')" class="mt-2" />
            </div>
            <x-secondary-button type="submit">Buat Recovery Code Baru</x-secondary-button>
            <p class="text-xs text-ink/50">Recovery code lama tidak berlaku lagi setelah dibuat yang baru.</p>
        </form>

        <div>
            <div class="flex items-center justify-between gap-4">
                <h3 class="text-sm font-semibold text-ink">Perangkat tepercaya</h3>
                @if ($trustedDevices->isNotEmpty())
                    <form method="post" action="{{ route('profile.trusted-devices.destroy-all') }}">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn-danger btn-sm">Cabut semua</button>
                    </form>
                @endif
            </div>

            @forelse ($trustedDevices as $device)
                <div class="mt-3 flex items-start justify-between gap-4 rounded-xl border border-primary-100 bg-white px-4 py-3">
                    <span class="icon-tile w-9 h-9 shrink-0"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0V12a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 12V5.25" /></svg></span>
                    <div class="text-sm flex-1 min-w-0">
                        <p class="font-medium text-ink">{{ \App\Support\UserAgentSummary::summarize($device->user_agent) }}</p>
                        <p class="text-ink/60">IP {{ $device->ip_address }}</p>
                        <p class="text-ink/60">Terakhir dipakai: {{ $device->last_used_at ? $formatDate($device->last_used_at) : 'Belum pernah' }}</p>
                        <p class="text-ink/60">Berlaku hingga: {{ $formatDate($device->expires_at) }}</p>
                    </div>
                    <form method="post" action="{{ route('profile.trusted-devices.destroy', $device->id) }}">
                        @csrf
                        @method('delete')
                        <button type="submit" class="btn-danger btn-sm">Cabut</button>
                    </form>
                </div>
            @empty
                <p class="mt-3 text-sm text-ink/60">Belum ada perangkat tepercaya.</p>
            @endforelse
        </div>
    </div>
</section>
