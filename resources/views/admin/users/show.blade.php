<x-admin-layout>
    <x-slot name="header">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-ink/50 hover:text-primary-700">&larr; Kembali ke Data Pengguna</a>
        <div class="mt-3 flex items-center gap-4">
            <span class="w-14 h-14 shrink-0 rounded-2xl bg-gradient-to-br from-primary-400 to-primary-600 text-white flex items-center justify-center text-xl font-display font-extrabold shadow-glow">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </span>
            <div class="min-w-0">
                <h1 class="text-2xl sm:text-3xl font-extrabold text-ink truncate">{{ $user->name }}</h1>
                <p class="text-sm text-ink/60 mt-0.5">{{ $user->email }}</p>
            </div>
        </div>
    </x-slot>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6 card p-4">
        <span class="inline-flex items-center gap-2 text-sm font-semibold {{ $user->hasTwoFactorEnabled() ? 'text-primary-700' : 'text-ink/60' }}">
            <span class="icon-tile w-9 h-9 {{ $user->hasTwoFactorEnabled() ? '' : '!bg-amber-50 !text-amber-600 !ring-amber-200' }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" /></svg>
            </span>
            {{ $user->hasTwoFactorEnabled() ? '2FA aktif' : '2FA belum diatur' }}
        </span>
        @if (! auth()->user()->is($user))
            <form method="POST" action="{{ route('admin.users.reset-two-factor', $user) }}"
                  data-confirm="Reset 2FA pengguna ini? Mereka harus mengaturnya ulang saat login berikutnya.">
                @csrf
                <button type="submit" class="btn-danger btn-sm">Reset 2FA</button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        @foreach ([
            ['Favorit', $user->favorites->count(), 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'],
            ['Ulasan', $user->reviews->count(), 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z'],
            ['Itinerary', $user->itineraries->count(), 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
        ] as [$label, $value, $icon])
            <div class="card p-5 flex items-center gap-4">
                <span class="icon-tile w-11 h-11">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
                </span>
                <div>
                    <p class="text-xs font-semibold text-ink/50">{{ $label }}</p>
                    <p class="font-display text-2xl font-extrabold text-ink">{{ $value }}</p>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-6">
            <h2 class="font-bold text-ink">Destinasi Favorit</h2>
            <ul class="mt-4 space-y-2 text-sm">
                @forelse ($user->favorites as $favorite)
                    <li class="flex items-center gap-2 text-ink/70">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent-400"></span>
                        {{ $favorite->destination->name ?? 'Destinasi telah dihapus' }}
                    </li>
                @empty
                    <li class="text-ink/50">Belum ada favorit.</li>
                @endforelse
            </ul>
        </div>

        <div class="card p-6">
            <h2 class="font-bold text-ink">Ulasan</h2>
            <ul class="mt-4 space-y-2 text-sm">
                @forelse ($user->reviews as $review)
                    <li class="flex items-center justify-between gap-3 text-ink/70">
                        <span class="truncate">{{ $review->destination->name ?? 'Destinasi telah dihapus' }}</span>
                        <span class="shrink-0 badge-warning">{{ $review->rating }}/5</span>
                    </li>
                @empty
                    <li class="text-ink/50">Belum ada ulasan.</li>
                @endforelse
            </ul>
        </div>

        <div class="card p-6 lg:col-span-2">
            <h2 class="font-bold text-ink">Itinerary</h2>
            <ul class="mt-4 divide-y divide-primary-50 text-sm">
                @forelse ($user->itineraries as $itinerary)
                    <li class="py-2.5 first:pt-0 flex items-center justify-between gap-3">
                        <span class="font-medium text-ink truncate">{{ $itinerary->title }}</span>
                        <span class="shrink-0 text-xs text-ink/50">{{ $itinerary->start_date->translatedFormat('d M Y') }} - {{ $itinerary->end_date->translatedFormat('d M Y') }}</span>
                    </li>
                @empty
                    <li class="text-ink/50">Belum ada itinerary.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-admin-layout>
