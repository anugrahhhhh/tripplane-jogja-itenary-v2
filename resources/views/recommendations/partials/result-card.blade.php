{{-- Satu kartu hasil rekomendasi AI. $item = ['destination' => ..., 'reason' => ...], $rank, $selectable --}}
@php $destination = $item['destination']; @endphp

<div class="relative block sm:w-64 shrink-0 aspect-[16/10] sm:aspect-auto bg-primary-50 overflow-hidden">
    @if ($selectable)
        <input type="checkbox" name="destination_ids[]" value="{{ $destination->id }}" x-model="selected" aria-label="Pilih {{ $destination->name }}"
            class="absolute top-3 left-3 w-6 h-6 rounded-lg border-white/80 bg-white/90 text-primary focus:ring-primary z-10 shadow">
    @endif
    @if ($destination->cover_photo)
        <img src="{{ asset('storage/'.$destination->cover_photo) }}" alt="{{ $destination->name }}" class="absolute inset-0 w-full h-full object-cover transition duration-500 group-hover:scale-105" loading="lazy" decoding="async">
    @else
        <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-primary-50 to-accent-50 text-primary-300">
            <x-category-icon :slug="$destination->category->slug" class="w-12 h-12" />
        </div>
    @endif
    <span class="absolute bottom-3 left-3 w-9 h-9 rounded-full bg-primary-900/85 backdrop-blur text-white font-display font-extrabold text-sm flex items-center justify-center ring-2 ring-white/70">#{{ $rank }}</span>
</div>

<div class="p-5 sm:p-6 flex-1 min-w-0">
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <span class="badge-primary">
                <x-category-icon :slug="$destination->category->slug" class="w-3 h-3" />
                {{ $rank }}. {{ $destination->category->name }}
            </span>
            <h3 class="mt-2 font-bold text-ink text-lg sm:text-xl">{{ $destination->name }}</h3>
            <p class="mt-0.5 flex items-center gap-1 text-sm text-ink/50">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                <span class="truncate">{{ \Illuminate\Support\Str::before($destination->address, ',') }}</span>
            </p>
        </div>
        @if (isset($destination->distance_km))
            <span class="badge-muted shrink-0">{{ $destination->distance_km }} km</span>
        @endif
    </div>

    <div class="mt-4 rounded-xl bg-gradient-to-br from-accent-50 to-cream ring-1 ring-accent-100 px-4 py-3">
        <p class="flex items-center gap-1.5 text-xs font-bold text-accent-600 uppercase tracking-wide mb-1">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
            Kenapa cocok untukmu
        </p>
        <p class="text-sm text-ink/80 leading-relaxed">{{ $item['reason'] }}</p>
    </div>

    <a href="{{ route('destinations.show', $destination) }}" @if ($selectable) onclick="event.stopPropagation()" @endif class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-accent-500 hover:underline">
        Lihat detail &rarr;
    </a>
</div>
