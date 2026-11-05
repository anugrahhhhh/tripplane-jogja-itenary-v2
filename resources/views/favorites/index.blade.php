<x-app-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <span class="w-14 h-14 rounded-2xl bg-white shadow-soft ring-1 ring-accent-200 flex items-center justify-center text-accent-500">
                    <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 24 24"><path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                </span>
                <div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-ink">Favorit Saya</h1>
                    <p class="text-sm text-ink/60 mt-1">{{ $favorites->total() }} destinasi tersimpan.</p>
                </div>
            </div>
            @if ($favorites->isNotEmpty())
                <a href="{{ route('itineraries.create') }}" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Susun jadi itinerary
                </a>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if ($favorites->isEmpty())
            <div class="empty-state">
                <span class="w-16 h-16 rounded-2xl bg-accent-50 ring-1 ring-accent-200 text-accent-400 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                </span>
                <h2 class="text-lg font-bold text-ink">Belum ada favorit</h2>
                <p class="mt-1 text-ink/60">Kamu belum menyimpan destinasi favorit.</p>
                <a href="{{ route('destinations.index') }}" class="mt-6 btn-primary">
                    Jelajahi Katalog
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @foreach ($favorites as $favorite)
                    <x-destination-card :destination="$favorite->destination" />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $favorites->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
