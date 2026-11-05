@php
    $avgRating = $destination->averageRating();
    $reviewsCount = $destination->reviewsCount();
    $openTime = \Illuminate\Support\Carbon::parse($destination->open_time)->format('H:i');
    $closeTime = \Illuminate\Support\Carbon::parse($destination->close_time)->format('H:i');
    $gallery = collect();
    if ($destination->cover_photo) {
        $gallery->push(['src' => asset('storage/'.$destination->cover_photo), 'alt' => $destination->name]);
    }
    foreach ($destination->photos as $photo) {
        $gallery->push(['src' => asset('storage/'.$photo->path), 'alt' => $destination->name]);
    }
@endphp

<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8" x-data="{ addOpen: false, photo: 0, photos: {{ \Illuminate\Support\Js::from($gallery->pluck('src')) }} }">
        <nav class="flex items-center gap-1.5 text-sm text-ink/50 mb-4 overflow-x-auto scrollbar-none whitespace-nowrap" aria-label="Breadcrumb">
            <a href="{{ route('destinations.index') }}" class="hover:text-primary-700">Katalog</a>
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            <a href="{{ route('destinations.by-category', $destination->category) }}" class="hover:text-primary-700">{{ $destination->category->name }}</a>
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            <span class="text-ink font-semibold truncate">{{ $destination->name }}</span>
        </nav>

        <!-- Hero -->
        <div class="relative rounded-[2rem] overflow-hidden h-80 sm:h-[28rem] bg-gradient-to-br from-primary-700 to-primary-900 shadow-lift">
            @if ($gallery->isNotEmpty())
                <template x-for="(src, i) in photos" :key="i">
                    <img :src="src" alt="{{ $destination->name }}" x-show="photo === i"
                         x-transition:enter="transition-opacity duration-500" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                         class="absolute inset-0 w-full h-full object-cover">
                </template>
                <noscript><img src="{{ $gallery->first()['src'] }}" alt="{{ $destination->name }}" class="absolute inset-0 w-full h-full object-cover"></noscript>
            @else
                <div class="absolute inset-0 pattern-dots opacity-60"></div>
                <div class="absolute inset-0 flex items-center justify-center text-white/30">
                    <x-category-icon :slug="$destination->category->slug" class="w-24 h-24" />
                </div>
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-black/30"></div>

            <a href="{{ route('destinations.by-category', $destination->category) }}" class="absolute top-5 left-5 inline-flex items-center gap-1.5 rounded-full bg-white/95 backdrop-blur px-4 py-2 text-sm font-semibold text-ink shadow-sm hover:bg-white transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                Kembali ke Katalog
            </a>

            @if ($gallery->count() > 1)
                <div class="absolute top-5 right-5 flex items-center gap-2">
                    <button type="button" @click="photo = (photo - 1 + photos.length) % photos.length" aria-label="Foto sebelumnya" class="w-10 h-10 rounded-full bg-white/90 backdrop-blur text-ink flex items-center justify-center hover:bg-white shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" /></svg>
                    </button>
                    <span class="rounded-full bg-black/40 backdrop-blur px-3 py-1.5 text-xs font-semibold text-white" x-text="(photo + 1) + ' / ' + photos.length"></span>
                    <button type="button" @click="photo = (photo + 1) % photos.length" aria-label="Foto berikutnya" class="w-10 h-10 rounded-full bg-white/90 backdrop-blur text-ink flex items-center justify-center hover:bg-white shadow-sm transition">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
                    </button>
                </div>
            @endif

            <div class="absolute bottom-6 left-6 right-6 sm:bottom-8 sm:left-8 sm:right-8">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-accent-100/95 backdrop-blur px-3 py-1 text-xs font-bold tracking-wide uppercase text-accent-600">
                    <x-category-icon :slug="$destination->category->slug" class="w-3.5 h-3.5" />
                    {{ $destination->category->name }}
                </span>
                <h1 class="mt-3 text-3xl sm:text-5xl font-extrabold text-white text-balance drop-shadow">{{ $destination->name }}</h1>
                <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2 text-sm text-white/90">
                    <span class="inline-flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                        {{ \Illuminate\Support\Str::before($destination->address, ',') }}, Yogyakarta
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/15 backdrop-blur px-2.5 py-1">
                        <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.957a1 1 0 00-.363-1.118L2.062 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" /></svg>
                        <span class="font-semibold">{{ $avgRating > 0 ? number_format($avgRating, 1) : 'Baru' }}</span>
                        <span class="text-white/70">({{ $reviewsCount }} ulasan)</span>
                    </span>
                </div>
            </div>
        </div>

        @if ($gallery->count() > 1)
            <div class="mt-3 flex gap-2 overflow-x-auto scrollbar-none pb-1">
                @foreach ($gallery as $index => $image)
                    <button type="button" @click="photo = {{ $index }}" aria-label="Lihat foto {{ $index + 1 }}"
                            :class="photo === {{ $index }} ? 'ring-2 ring-primary ring-offset-2 ring-offset-cream opacity-100' : 'opacity-60 hover:opacity-100'"
                            class="shrink-0 w-20 h-14 sm:w-24 sm:h-16 rounded-xl overflow-hidden transition">
                        <img src="{{ $image['src'] }}" alt="" class="w-full h-full object-cover" loading="lazy" decoding="async">
                    </button>
                @endforeach
            </div>
        @endif

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-8 lg:gap-10">
            <div class="lg:col-span-2 space-y-6">
                <!-- Info cepat -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div class="card p-4 flex items-start gap-3">
                        <span class="icon-tile">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-ink/50">Lokasi</p>
                            <p class="mt-0.5 font-semibold text-ink text-sm">{{ $destination->address }}</p>
                        </div>
                    </div>
                    <div class="card p-4 flex items-start gap-3">
                        <span class="icon-tile">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-ink/50">Jam Operasional</p>
                            <p class="mt-0.5 font-semibold text-ink text-sm">{{ $openTime }} &mdash; {{ $closeTime }} WIB</p>
                            @if (! empty($destination->closed_days))
                                <p class="mt-1 text-xs font-medium text-red-500">Tutup: {{ \App\Support\Weekday::names($destination->closed_days) }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="card p-4 flex items-start gap-3">
                        <span class="icon-tile">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" /></svg>
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-ink/50">Harga Tiket</p>
                            <p class="mt-0.5 font-semibold text-ink text-sm">{{ \App\Support\Rupiah::range((int) $destination->price_min, (int) $destination->price_max) }}</p>
                        </div>
                    </div>
                    <div class="card p-4 flex items-start gap-3">
                        <span class="icon-tile">
                            <x-category-icon :slug="$destination->category->slug" class="w-5 h-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-ink/50">Kategori</p>
                            <p class="mt-0.5 font-semibold text-ink text-sm">{{ $destination->category->name }}</p>
                        </div>
                    </div>
                </div>

                <div class="card p-6">
                    <h2 class="text-lg font-bold text-ink">Tentang Tempat Ini</h2>
                    <p class="mt-3 text-ink/70 leading-relaxed whitespace-pre-line">{{ $destination->description }}</p>
                </div>

                @if ($destination->recommendation_reason)
                    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-accent-50 to-primary-50 ring-1 ring-accent-200 p-6">
                        <div class="flex items-center gap-2">
                            <span class="w-8 h-8 rounded-lg bg-white text-accent-500 flex items-center justify-center shadow-sm">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" /></svg>
                            </span>
                            <h2 class="text-lg font-bold text-ink">Kenapa Tempat Ini Direkomendasikan?</h2>
                        </div>
                        <p class="mt-3 text-ink/70 leading-relaxed">{{ $destination->recommendation_reason }}</p>
                    </div>
                @endif

                <!-- Ulasan -->
                <div id="ulasan" class="card p-6 scroll-mt-24">
                    <div class="flex items-center justify-between flex-wrap gap-4">
                        <h2 class="text-lg font-bold text-ink">Ulasan ({{ $reviewsCount }})</h2>
                        <div class="flex items-center gap-3">
                            <span class="font-display text-3xl font-extrabold text-ink">{{ number_format($avgRating, 1) }}</span>
                            <div>
                                <div class="flex items-center gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-4 h-4 {{ $i <= round($avgRating) ? 'text-amber-400' : 'text-ink/15' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.957a1 1 0 00-.363-1.118L2.062 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" /></svg>
                                    @endfor
                                </div>
                                <p class="text-xs text-ink/50">dari 5 &middot; {{ $reviewsCount }} ulasan</p>
                            </div>
                        </div>
                    </div>

                    @auth
                        <form action="{{ route('reviews.store', $destination) }}" method="POST" class="mt-5 rounded-2xl bg-cream/70 ring-1 ring-primary-100 p-4 sm:p-5" x-data="{ rating: {{ (int) old('rating', 5) }}, hover: 0 }">
                            @csrf
                            <p class="text-sm font-semibold text-ink">Tulis ulasanmu</p>
                            <div class="mt-2 flex items-center gap-1" @mouseleave="hover = 0">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button" @click="rating = {{ $i }}" @mouseenter="hover = {{ $i }}" aria-label="Beri {{ $i }} bintang" class="focus:outline-none transition hover:scale-110">
                                        <svg class="w-7 h-7" :class="(hover || rating) >= {{ $i }} ? 'text-amber-400' : 'text-ink/20'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.957a1 1 0 00-.363-1.118L2.062 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" /></svg>
                                    </button>
                                @endfor
                                <span class="ml-2 text-sm font-semibold text-ink/60" x-text="(hover || rating) + '/5'"></span>
                                <input type="hidden" name="rating" :value="rating">
                            </div>
                            <label for="review-comment" class="sr-only">Komentar</label>
                            <textarea id="review-comment" name="comment" rows="3" placeholder="Ceritakan pengalamanmu..." class="input mt-3">{{ old('comment') }}</textarea>
                            <x-input-error :messages="$errors->get('rating')" class="mt-1" />
                            <x-input-error :messages="$errors->get('comment')" class="mt-1" />
                            <div class="mt-3 text-right">
                                <x-primary-button>Kirim Ulasan</x-primary-button>
                            </div>
                        </form>
                    @else
                        <div class="mt-5 rounded-2xl bg-cream/70 ring-1 ring-primary-100 p-4 text-sm text-ink/60">
                            <a href="{{ route('login') }}" class="link">Masuk</a> untuk menulis ulasan.
                        </div>
                    @endauth

                    <div class="mt-6 divide-y divide-primary-50">
                        @forelse ($reviews as $review)
                            <div class="py-4 first:pt-0 flex gap-3">
                                <span class="w-10 h-10 shrink-0 rounded-full bg-gradient-to-br from-primary-100 to-accent-100 text-primary-700 flex items-center justify-center text-sm font-bold">
                                    {{ strtoupper(substr($review->user->name, 0, 1)) }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <p class="font-semibold text-ink text-sm">{{ $review->user->name }}</p>
                                        <div class="flex items-center gap-0.5">
                                            @for ($i = 1; $i <= 5; $i++)
                                                <svg class="w-3.5 h-3.5 {{ $i <= $review->rating ? 'text-amber-400' : 'text-ink/15' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.957a1 1 0 00-.363-1.118L2.062 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" /></svg>
                                            @endfor
                                        </div>
                                    </div>
                                    <p class="text-xs text-ink/40">{{ $review->created_at->translatedFormat('d F Y') }}</p>
                                    @if ($review->comment)
                                        <p class="mt-2 text-sm text-ink/70 leading-relaxed">{{ $review->comment }}</p>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="py-8 text-center">
                                <p class="text-sm font-medium text-ink/60">Belum ada ulasan. Jadilah yang pertama!</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($reviews->hasPages())
                        <nav class="mt-4 pt-4 border-t border-primary-50" aria-label="Halaman ulasan">
                            {{ $reviews->links() }}
                        </nav>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="lg:sticky lg:top-24 space-y-5">
                    <div class="card p-5">
                        <h3 class="text-lg font-bold text-ink">Atur Perjalananmu</h3>
                        <p class="mt-1 text-sm text-ink/60">Tambahkan tempat ini ke rencana perjalanan atau simpan sebagai favorit.</p>

                        <div class="mt-5 space-y-2.5">
                            @auth
                                <button type="button" @click="addOpen = ! addOpen" :aria-expanded="addOpen" class="btn-primary w-full py-3">
                                    <svg class="w-4 h-4 transition" :class="addOpen && 'rotate-45'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                    Tambahkan ke Itinerary
                                </button>
                            @endauth

                            @auth
                                <div x-show="addOpen" x-cloak x-transition class="space-y-3 pb-1">
                                    @if ($userItineraries->isEmpty())
                                        <p class="rounded-xl bg-cream p-3 text-sm text-ink/60">Kamu belum punya itinerary. <a href="{{ route('itineraries.create') }}" class="link">Buat itinerary baru</a> dulu.</p>
                                    @else
                                        @foreach ($userItineraries as $itinerary)
                                            <form action="{{ route('itinerary-items.store', $itinerary) }}" method="POST" class="rounded-xl bg-cream/70 ring-1 ring-primary-100 p-3">
                                                @csrf
                                                <input type="hidden" name="destination_id" value="{{ $destination->id }}">
                                                <p class="text-sm font-semibold text-ink truncate">{{ $itinerary->title }}</p>
                                                <div class="mt-2 grid grid-cols-2 gap-2">
                                                    <select name="day_number" aria-label="Hari" class="input text-xs py-2">
                                                        @foreach (range(1, $itinerary->totalDays()) as $day)
                                                            <option value="{{ $day }}">Hari ke-{{ $day }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input type="time" name="start_time" value="09:00" required aria-label="Jam mulai" class="input text-xs py-2">
                                                </div>
                                                <input type="number" name="duration_minutes" value="60" min="15" step="15" aria-label="Durasi (menit)" class="input mt-2 text-xs py-2" placeholder="Durasi (menit)">
                                                <button type="submit" class="btn-primary btn-sm w-full mt-2 py-2">
                                                    Tambahkan ke "{{ \Illuminate\Support\Str::limit($itinerary->title, 20) }}"
                                                </button>
                                            </form>
                                        @endforeach
                                    @endif
                                </div>
                            @endauth

                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-1 gap-2.5">
                                @auth
                                    <form action="{{ $isFavorited ? route('favorites.destroy', $destination) : route('favorites.store', $destination) }}" method="POST">
                                        @csrf
                                        @if ($isFavorited) @method('DELETE') @endif
                                        <button type="submit" class="btn w-full border {{ $isFavorited ? 'border-accent-300 bg-accent-50 text-accent-600' : 'border-accent-200 bg-white text-accent-500 hover:bg-accent-50' }}">
                                            <svg class="w-4 h-4" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                            </svg>
                                            {{ $isFavorited ? 'Tersimpan di Favorit' : 'Simpan ke Favorit' }}
                                        </button>
                                    </form>
                                @else
                                    <a href="{{ route('login') }}" class="btn w-full border border-accent-200 bg-white text-accent-500 hover:bg-accent-50">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                                        Simpan ke Favorit
                                    </a>
                                @endauth

                                <button type="button" onclick="shareDestination()" class="btn-secondary w-full">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" /></svg>
                                    Bagikan Tempat
                                </button>
                            </div>
                        </div>

                        <div class="mt-5 rounded-xl bg-gradient-to-br from-primary-50 to-cream ring-1 ring-primary-100 p-4">
                            <p class="flex items-center gap-1.5 text-xs font-bold text-primary-700">
                                <svg class="w-4 h-4 text-accent-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" /></svg>
                                Contoh:
                            </p>
                            <p class="mt-2 text-xs font-semibold text-ink/50">Hari 01 &middot; 15:00</p>
                            <p class="text-xs text-ink/70">Setelah makan siang, kunjungi {{ $destination->name }}.</p>
                        </div>
                    </div>

                    <div class="card p-4">
                        <div class="flex items-center justify-between gap-2 mb-3">
                            <p class="flex items-center gap-1.5 text-sm font-bold text-ink">
                                <svg class="w-4 h-4 text-accent-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                Lihat lokasi di peta
                            </p>
                            <a href="https://www.google.com/maps/search/?api=1&query={{ $destination->latitude }},{{ $destination->longitude }}" target="_blank" rel="noopener noreferrer" class="text-xs font-semibold text-accent-500 hover:underline">Buka rute &nearr;</a>
                        </div>
                        <div id="map" class="h-56 rounded-xl ring-1 ring-primary-100 overflow-hidden z-0"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related -->
        @if ($related->isNotEmpty())
            <div class="mt-16">
                <p class="eyebrow">Masih di sekitar sini</p>
                <h2 class="mt-2 section-title">Tempat Lain yang Mungkin Kamu Suka</h2>
                <div class="mt-6 grid grid-cols-1 min-[440px]:grid-cols-2 lg:grid-cols-4 gap-5">
                    @foreach ($related as $item)
                        <x-destination-card :destination="$item" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <x-slot name="head">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const lat = {{ $destination->latitude }};
                const lng = {{ $destination->longitude }};
                const map = L.map('map', { scrollWheelZoom: false }).setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(map);
                L.marker([lat, lng]).addTo(map).bindPopup(@json($destination->name));
            });

            function shareDestination() {
                const shareData = {
                    title: @json($destination->name),
                    text: 'Lihat destinasi ini di JogjaTrip',
                    url: window.location.href,
                };

                if (navigator.share) {
                    navigator.share(shareData).catch(() => {});
                } else if (navigator.clipboard) {
                    navigator.clipboard.writeText(window.location.href)
                        .then(() => alert('Tautan disalin ke clipboard!'))
                        .catch(() => prompt('Salin tautan ini:', window.location.href));
                } else {
                    prompt('Salin tautan ini:', window.location.href);
                }
            }
        </script>
    </x-slot>
</x-app-layout>
