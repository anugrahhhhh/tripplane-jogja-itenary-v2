@php
    $securityUser = auth()->user();
    $trustedDevices = $securityUser->trustedDevices()->orderByDesc('last_used_at')->orderByDesc('id')->get();
    $formatDate = fn ($date) => $date?->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i').' WIB';
@endphp

<section>
    <header>
        <h2 class="text-lg font-semibold text-ink">Keamanan</h2>
        <p class="mt-1 text-sm text-ink/60">Kelola verifikasi dua langkah, recovery code, dan perangkat tepercaya.</p>
    </header>

    @if (session('security_status') === 'perangkat-dicabut')
        <p class="mt-4 text-sm text-primary-700">Perangkat berhasil dicabut.</p>
    @elseif (session('security_status') === 'semua-perangkat-dicabut')
        <p class="mt-4 text-sm text-primary-700">Semua perangkat tepercaya berhasil dicabut.</p>
    @endif

    <div class="mt-6 space-y-6">
        <div>
            <p class="text-sm text-ink/70">
                Verifikasi dua langkah:
                <span class="font-semibold text-primary-700">{{ $securityUser->hasTwoFactorEnabled() ? 'Aktif' : 'Belum aktif' }}</span>
            </p>
            <p class="mt-1 text-sm text-ink/70">
                Recovery code tersisa: <span class="font-semibold">{{ $securityUser->recoveryCodesRemaining() }} dari 8</span>
            </p>
        </div>

        <form method="post" action="{{ route('profile.recovery-codes.regenerate') }}" class="space-y-3">
            @csrf
            <div>
                <x-input-label for="regenerate_code" value="Kode dari aplikasi autentikator" />
                <x-text-input id="regenerate_code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" class="mt-1 block w-full" required />
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
                        <button type="submit" class="text-sm text-red-600 hover:underline">Cabut semua</button>
                    </form>
                @endif
            </div>

            @forelse ($trustedDevices as $device)
                <div class="mt-3 flex items-start justify-between gap-4 rounded-lg border border-primary-100 px-4 py-3">
                    <div class="text-sm">
                        <p class="font-medium text-ink">{{ \App\Support\UserAgentSummary::summarize($device->user_agent) }}</p>
                        <p class="text-ink/60">IP {{ $device->ip_address }}</p>
                        <p class="text-ink/60">Terakhir dipakai: {{ $device->last_used_at ? $formatDate($device->last_used_at) : 'Belum pernah' }}</p>
                        <p class="text-ink/60">Berlaku hingga: {{ $formatDate($device->expires_at) }}</p>
                    </div>
                    <form method="post" action="{{ route('profile.trusted-devices.destroy', $device->id) }}">
                        @csrf
                        @method('delete')
                        <button type="submit" class="text-sm text-red-600 hover:underline">Cabut</button>
                    </form>
                </div>
            @empty
                <p class="mt-3 text-sm text-ink/60">Belum ada perangkat tepercaya.</p>
            @endforelse
        </div>
    </div>
</section>
