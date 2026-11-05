@props(['destination'])

@php
    // withAvg()/withCount() dari controller memberi nilai null/0 untuk
    // destinasi tanpa ulasan; itu tetap jawaban valid, jadi jangan query ulang.
    $attributes = $destination->getAttributes();
    $rating = array_key_exists('reviews_avg_rating', $attributes)
        ? round((float) $destination->reviews_avg_rating, 1)
        : $destination->averageRating();
    $reviewsCount = array_key_exists('reviews_count', $attributes)
        ? (int) $destination->reviews_count
        : $destination->reviewsCount();
    $area = \Illuminate\Support\Str::before($destination->address, ',');
    $isFavorited = $destination->isFavoritedBy(auth()->user());
    $priceMin = (int) $destination->price_min;
    $priceMax = (int) $destination->price_max;
@endphp

<div class="group relative h-full flex flex-col bg-white rounded-2xl border border-primary-100/70 shadow-soft overflow-hidden transition duration-300 hover:shadow-lift hover:-translate-y-1 hover:border-primary-200">
    <a href="{{ route('destinations.show', $destination) }}" class="block" tabindex="-1" aria-hidden="true">
        <div class="relative aspect-[4/3] bg-primary-50 overflow-hidden">
            @if ($destination->cover_photo)
                <img src="{{ asset('storage/'.$destination->cover_photo) }}" alt="{{ $destination->name }}" class="w-full h-full object-cover transition duration-500 group-hover:scale-110" loading="lazy" decoding="async">
            @else
                <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-50 to-accent-50 text-primary-300">
                    <x-category-icon :slug="$destination->category->slug" class="w-12 h-12" />
                </div>
            @endif

            <div class="absolute inset-x-0 bottom-0 h-1/2 bg-gradient-to-t from-black/50 to-transparent opacity-80 transition group-hover:opacity-100"></div>

            <span class="absolute bottom-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-white/95 backdrop-blur px-2.5 py-1 text-[11px] font-semibold text-primary-700 shadow-sm">
                <x-category-icon :slug="$destination->category->slug" class="w-3.5 h-3.5" />
                {{ $destination->category->name }}
            </span>

            @isset($destination->distance_km)
                <span class="absolute top-3 left-3 inline-flex items-center gap-1 rounded-full bg-primary-900/80 backdrop-blur px-2.5 py-1 text-xs font-semibold text-white shadow-sm">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                    {{ $destination->distance_km }} km
                </span>
            @endisset
        </div>
    </a>

    @auth
        <form action="{{ $isFavorited ? route('favorites.destroy', $destination) : route('favorites.store', $destination) }}" method="POST" class="absolute top-3 right-3 z-10">
            @csrf
            @if ($isFavorited) @method('DELETE') @endif
            <button type="submit" title="{{ $isFavorited ? 'Hapus dari favorit' : 'Simpan ke favorit' }}" aria-label="{{ $isFavorited ? 'Hapus dari favorit' : 'Simpan ke favorit' }}" class="w-9 h-9 rounded-full bg-white/95 backdrop-blur flex items-center justify-center shadow-sm hover:scale-110 active:scale-95 transition {{ $isFavorited ? 'text-accent-500' : 'text-ink/40 hover:text-accent-500' }}">
                <svg class="w-[18px] h-[18px]" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                </svg>
            </button>
        </form>
    @else
        <a href="{{ route('login') }}" title="Masuk untuk menyimpan favorit" aria-label="Masuk untuk menyimpan favorit" class="absolute top-3 right-3 z-10 w-9 h-9 rounded-full bg-white/95 backdrop-blur flex items-center justify-center shadow-sm hover:scale-110 transition text-ink/40 hover:text-accent-500">
            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
            </svg>
        </a>
    @endauth

    <a href="{{ route('destinations.show', $destination) }}" class="flex-1 flex flex-col p-4">
        <h3 class="font-bold text-ink line-clamp-1 group-hover:text-primary-700 transition">{{ $destination->name }}</h3>
        <p class="mt-1 flex items-center gap-1 text-sm text-ink/50 line-clamp-1">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
            <span class="truncate">{{ $area }}</span>
        </p>

        <div class="mt-auto pt-4 flex items-center justify-between gap-2">
            <div class="flex items-center gap-1.5 text-sm">
                <span class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-2 py-0.5 font-semibold text-amber-700">
                    <svg class="w-3.5 h-3.5 text-amber-400" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.957a1 1 0 00-.363-1.118L2.062 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" />
                    </svg>
                    {{ $rating > 0 ? number_format($rating, 1) : 'Baru' }}
                </span>
                @if ($reviewsCount > 0)
                    <span class="text-xs text-ink/40">({{ $reviewsCount }})</span>
                @endif
            </div>
            <span class="text-xs font-semibold {{ $priceMin === 0 && $priceMax === 0 ? 'text-primary-600' : 'text-ink/60' }} truncate">
                @if ($priceMin === 0 && $priceMax === 0)
                    Gratis
                @elseif ($priceMin === 0)
                    Gratis &ndash; {{ \App\Support\Rupiah::format($priceMax) }}
                @else
                    Mulai {{ \App\Support\Rupiah::format($priceMin) }}
                @endif
            </span>
        </div>
    </a>
</div>
