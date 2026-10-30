<x-admin-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink">{{ $user->name }}</h1>
        <p class="text-sm text-ink/60 mt-1">{{ $user->email }}</p>
    </x-slot>

    <div class="flex flex-wrap items-center gap-3 mb-6 bg-white border border-primary-100 rounded-2xl p-4">
        <span class="text-sm font-medium {{ $user->hasTwoFactorEnabled() ? 'text-primary-700' : 'text-ink/60' }}">
            {{ $user->hasTwoFactorEnabled() ? '2FA aktif' : '2FA belum diatur' }}
        </span>
        @if (! auth()->user()->is($user))
            <form method="POST" action="{{ route('admin.users.reset-two-factor', $user) }}"
                  onsubmit="return confirm('Reset 2FA pengguna ini? Mereka harus mengaturnya ulang saat login berikutnya.')">
                @csrf
                <button type="submit" class="text-sm px-3 py-1.5 rounded-lg border border-red-200 text-red-700 hover:bg-red-50">Reset 2FA</button>
            </form>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="bg-white border border-primary-100 rounded-2xl p-4">
            <p class="text-xs text-ink/50">Favorit</p>
            <p class="text-xl font-bold text-primary-700">{{ $user->favorites->count() }}</p>
        </div>
        <div class="bg-white border border-primary-100 rounded-2xl p-4">
            <p class="text-xs text-ink/50">Ulasan</p>
            <p class="text-xl font-bold text-primary-700">{{ $user->reviews->count() }}</p>
        </div>
        <div class="bg-white border border-primary-100 rounded-2xl p-4">
            <p class="text-xs text-ink/50">Itinerary</p>
            <p class="text-xl font-bold text-primary-700">{{ $user->itineraries->count() }}</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border border-primary-100 rounded-2xl p-5">
            <h2 class="font-semibold text-ink">Destinasi Favorit</h2>
            <ul class="mt-3 space-y-1 text-sm">
                @forelse ($user->favorites as $favorite)
                    <li class="text-ink/70">{{ $favorite->destination->name ?? 'Destinasi telah dihapus' }}</li>
                @empty
                    <li class="text-ink/50">Belum ada favorit.</li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white border border-primary-100 rounded-2xl p-5">
            <h2 class="font-semibold text-ink">Ulasan</h2>
            <ul class="mt-3 space-y-2 text-sm">
                @forelse ($user->reviews as $review)
                    <li class="text-ink/70">{{ $review->destination->name ?? 'Destinasi telah dihapus' }} &mdash; {{ $review->rating }}/5</li>
                @empty
                    <li class="text-ink/50">Belum ada ulasan.</li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white border border-primary-100 rounded-2xl p-5 lg:col-span-2">
            <h2 class="font-semibold text-ink">Itinerary</h2>
            <ul class="mt-3 space-y-1 text-sm">
                @forelse ($user->itineraries as $itinerary)
                    <li class="text-ink/70">{{ $itinerary->title }} ({{ $itinerary->start_date->translatedFormat('d M Y') }} - {{ $itinerary->end_date->translatedFormat('d M Y') }})</li>
                @empty
                    <li class="text-ink/50">Belum ada itinerary.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <a href="{{ route('admin.users.index') }}" class="mt-6 inline-block text-sm text-primary-700 hover:underline">&larr; Kembali ke Data Pengguna</a>
</x-admin-layout>
