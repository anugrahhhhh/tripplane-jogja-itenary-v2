<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="{ addOpen: false }">
        <nav class="text-sm text-ink/50 mb-4">
            <a href="{{ route('destinations.index') }}" class="hover:text-primary">Katalog</a>
            <span class="mx-1">/</span>
            <a href="{{ route('destinations.by-category', $destination->category) }}" class="hover:text-primary">{{ $destination->category->name }}</a>
            <span class="mx-1">/</span>
            <span class="text-ink font-medium">{{ $destination->name }}</span>
        </nav>

        <!-- Hero -->
        <div class="relative rounded-3xl overflow-hidden h-72 sm:h-96 bg-primary-800">
            @if ($destination->cover_photo)
                <img src="{{ asset('storage/'.$destination->cover_photo) }}" alt="{{ $destination->name }}" class="absolute inset-0 w-full h-full object-cover">
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/10 to-black/20"></div>

            <a href="{{ route('destinations.by-category', $destination->category) }}" class="absolute top-5 left-5 inline-flex items-center gap-1.5 rounded-full bg-white/95 backdrop-blur px-4 py-2 text-sm font-semibold text-ink hover:bg-white transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" /></svg>
                Kembali ke Katalog
            </a>

            <div class="absolute bottom-6 left-6 right-6">
                <span class="inline-flex items-center rounded-full bg-accent-100/95 backdrop-blur px-3 py-1 text-xs font-semibold tracking-wide uppercase text-accent-600">
                    {{ $destination->category->name }}
                </span>
                <h1 class="mt-3 text-3xl sm:text-4xl font-bold text-white text-balance">{{ $destination->name }}</h1>
                <p class="mt-2 flex items-center gap-1.5 text-sm text-white/90">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                    {{ \Illuminate\Support\Str::before($destination->address, ',') }}, Yogyakarta
                    <span class="mx-1">&middot;</span>
                    <svg class="w-4 h-4 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.957a1 1 0 00-.363-1.118L2.062 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" /></svg>
                    {{ $destination->averageRating() > 0 ? number_format($destination->averageRating(), 1) : 'Baru' }}
                    ({{ $destination->reviewsCount() }} reviews)
                </p>
            </div>
        </div>

        <div class="mt-8 grid grid-cols-1 lg:grid-cols-3 gap-10">
            <div class="lg:col-span-2">
                <div class="flex items-center gap-2 text-sm text-ink/70 pb-6 border-b border-primary-100">
                    <div class="flex items-center gap-0.5">
                        @for ($i = 1; $i <= 5; $i++)
                            <svg class="w-4 h-4 {{ $i <= round($destination->averageRating()) ? 'text-amber-400' : 'text-ink/15' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.957a1 1 0 00-.363-1.118L2.062 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" /></svg>
                        @endfor
                    </div>
                    <span class="font-semibold text-ink">{{ number_format($destination->averageRating(), 1) }}/5</span>
                    <span class="text-ink/40">&middot;</span>
                    <span>Destinasi {{ strtolower($destination->category->name) }} populer di Yogyakarta</span>
                </div>

                <div class="py-6 border-b border-primary-100">
                    <h2 class="font-semibold text-ink text-lg">Tentang Tempat Ini</h2>
                    <p class="mt-3 text-ink/70 leading-relaxed">{{ $destination->description }}</p>
                </div>

                <div class="py-6 border-b border-primary-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="rounded-xl border border-primary-100 bg-cream/60 p-4">
                        <p class="flex items-center gap-1.5 text-xs text-ink/50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                            Lokasi
                        </p>
                        <p class="mt-1 font-semibold text-ink text-sm">{{ $destination->address }}</p>
                    </div>
                    <div class="rounded-xl border border-primary-100 bg-cream/60 p-4">
                        <p class="flex items-center gap-1.5 text-xs text-ink/50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            Jam Operasional
                        </p>
                        <p class="mt-1 font-semibold text-ink text-sm">{{ \Illuminate\Support\Carbon::parse($destination->open_time)->format('H:i') }} &mdash; {{ \Illuminate\Support\Carbon::parse($destination->close_time)->format('H:i') }} WIB</p>
                        @if (! empty($destination->closed_days))
                            <p class="mt-1 text-xs text-ink/60">Tutup: {{ \App\Support\Weekday::names($destination->closed_days) }}</p>
                        @endif
                    </div>
                    <div class="rounded-xl border border-primary-100 bg-cream/60 p-4">
                        <p class="flex items-center gap-1.5 text-xs text-ink/50">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3M3.75 19.5h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5z" /></svg>
                            Price Range
                        </p>
                        <p class="mt-1 font-semibold text-ink text-sm">
                            @if ($destination->price_min == 0 && $destination->price_max == 0)
                                Gratis
                            @else
                                Rp{{ number_format($destination->price_min) }} &mdash; Rp{{ number_format($destination->price_max) }}
                            @endif
                        </p>
                    </div>
                    <div class="rounded-xl border border-primary-100 bg-cream/60 p-4">
                        <p class="flex items-center gap-1.5 text-xs text-ink/50">
                            <x-category-icon :slug="$destination->category->slug" class="w-4 h-4" />
                            Kategori
                        </p>
                        <p class="mt-1 font-semibold text-ink text-sm">{{ $destination->category->name }}</p>
                    </div>
                </div>

                @if ($destination->recommendation_reason)
                    <div class="py-6 border-b border-primary-100">
                        <h2 class="font-semibold text-ink text-lg">Kenapa Tempat Ini Direkomendasikan?</h2>
                        <p class="mt-3 text-ink/70 leading-relaxed">{{ $destination->recommendation_reason }}</p>
                    </div>
                @endif

                <!-- Ulasan -->
                <div class="pt-6">
                    <h2 class="font-semibold text-ink text-lg">Ulasan ({{ $destination->reviewsCount() }})</h2>

                    @auth
                        <form action="{{ route('reviews.store', $destination) }}" method="POST" class="mt-4 bg-white border border-primary-100 rounded-xl p-4" x-data="{ rating: {{ old('rating', 5) }} }">
                            @csrf
                            <p class="text-sm font-medium text-ink">Tulis ulasanmu</p>
                            <div class="mt-2 flex gap-1">
                                @for ($i = 1; $i <= 5; $i++)
                                    <button type="button" @click="rating = {{ $i }}" class="focus:outline-none">
                                        <svg class="w-6 h-6" :class="rating >= {{ $i }} ? 'text-amber-400' : 'text-ink/20'" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.957a1 1 0 00-.363-1.118L2.062 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" /></svg>
                                    </button>
                                @endfor
                                <input type="hidden" name="rating" :value="rating">
                            </div>
                            <textarea name="comment" rows="3" placeholder="Ceritakan pengalamanmu..." class="mt-3 w-full rounded-lg border-primary-100 text-sm focus:border-primary focus:ring-primary">{{ old('comment') }}</textarea>
                            <x-input-error :messages="$errors->get('rating')" class="mt-1" />
                            <x-input-error :messages="$errors->get('comment')" class="mt-1" />
                            <div class="mt-3 text-right">
                                <x-primary-button>Kirim Ulasan</x-primary-button>
                            </div>
                        </form>
                    @else
                        <p class="mt-3 text-sm text-ink/60">
                            <a href="{{ route('login') }}" class="text-accent-500 font-medium underline">Masuk</a> untuk menulis ulasan.
                        </p>
                    @endauth

                    <div class="mt-6 space-y-4">
                        @forelse ($destination->visibleReviews as $review)
                            <div class="border-b border-primary-50 pb-4">
                                <div class="flex items-center justify-between">
                                    <p class="font-medium text-ink text-sm">{{ $review->user->name }}</p>
                                    <div class="flex items-center gap-0.5">
                                        @for ($i = 1; $i <= 5; $i++)
                                            <svg class="w-3.5 h-3.5 {{ $i <= $review->rating ? 'text-amber-400' : 'text-ink/15' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.957a1 1 0 00-.363-1.118L2.062 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" /></svg>
                                        @endfor
                                    </div>
                                </div>
                                @if ($review->comment)
                                    <p class="mt-1 text-sm text-ink/70">{{ $review->comment }}</p>
                                @endif
                                <p class="mt-1 text-xs text-ink/40">{{ $review->created_at->translatedFormat('d F Y') }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-ink/50">Belum ada ulasan. Jadilah yang pertama!</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="sticky top-24 bg-white border border-primary-100 rounded-2xl p-5">
                    <h3 class="font-semibold text-ink">Atur Perjalananmu</h3>
                    <p class="mt-1 text-sm text-ink/60">Tambahkan tempat ini ke rencana perjalanan atau simpan sebagai favorit.</p>

                    <div class="mt-4 space-y-2.5">
                        @auth
                            <form action="{{ $isFavorited ? route('favorites.destroy', $destination) : route('favorites.store', $destination) }}" method="POST">
                                @csrf
                                @if ($isFavorited) @method('DELETE') @endif
                                <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-semibold transition {{ $isFavorited ? 'border-accent-300 bg-accent-50 text-accent-600' : 'border-accent-200 text-accent-500 hover:bg-accent-50' }}">
                                    <svg class="w-4 h-4" fill="{{ $isFavorited ? 'currentColor' : 'none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" />
                                    </svg>
                                    {{ $isFavorited ? 'Tersimpan di Favorit' : 'Simpan ke Favorit' }}
                                </button>
                            </form>
                        @else
                            <a href="{{ route('login') }}" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-accent-200 px-4 py-2.5 text-sm font-semibold text-accent-500 hover:bg-accent-50 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                                Simpan ke Favorit
                            </a>
                        @endauth

                        <button type="button" onclick="shareDestination()" class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-primary-200 px-4 py-2.5 text-sm font-semibold text-ink/70 hover:bg-primary-50 transition">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 100 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186l9.566-5.314m-9.566 7.5l9.566 5.314m0 0a2.25 2.25 0 103.935 2.186 2.25 2.25 0 00-3.935-2.186zm0-12.814a2.25 2.25 0 103.933-2.185 2.25 2.25 0 00-3.933 2.185z" /></svg>
                            Bagikan Tempat
                        </button>

                        @auth
                            <button type="button" @click="addOpen = ! addOpen" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-600 transition">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                Tambahkan ke Itinerary
                            </button>
                        @endauth
                    </div>

                    @auth
                        <div x-show="addOpen" x-cloak class="mt-3 space-y-3">
                            @if ($userItineraries->isEmpty())
                                <p class="text-sm text-ink/60">Kamu belum punya itinerary. <a href="{{ route('itineraries.create') }}" class="text-accent-500 underline font-medium">Buat itinerary baru</a> dulu.</p>
                            @else
                                @foreach ($userItineraries as $itinerary)
                                    <form action="{{ route('itinerary-items.store', $itinerary) }}" method="POST" class="rounded-xl border border-primary-100 p-3">
                                        @csrf
                                        <input type="hidden" name="destination_id" value="{{ $destination->id }}">
                                        <p class="text-sm font-medium text-ink">{{ $itinerary->title }}</p>
                                        <div class="mt-2 grid grid-cols-2 gap-2">
                                            <select name="day_number" class="rounded-lg border-primary-100 text-xs focus:border-primary focus:ring-primary">
                                                @foreach (range(1, $itinerary->totalDays()) as $day)
                                                    <option value="{{ $day }}">Hari ke-{{ $day }}</option>
                                                @endforeach
                                            </select>
                                            <input type="time" name="start_time" value="09:00" required class="rounded-lg border-primary-100 text-xs focus:border-primary focus:ring-primary">
                                        </div>
                                        <input type="number" name="duration_minutes" value="60" min="15" step="15" class="mt-2 w-full rounded-lg border-primary-100 text-xs focus:border-primary focus:ring-primary" placeholder="Durasi (menit)">
                                        <button type="submit" class="mt-2 w-full rounded-lg bg-primary-700 text-white text-xs font-semibold py-2 hover:bg-primary-800 transition">
                                            Tambahkan ke "{{ \Illuminate\Support\Str::limit($itinerary->title, 20) }}"
                                        </button>
                                    </form>
                                @endforeach
                            @endif
                        </div>
                    @endauth

                    <div class="mt-4 rounded-xl bg-cream border border-primary-100 p-4">
                        <p class="flex items-center gap-1.5 text-xs font-semibold text-ink/60">
                            <svg class="w-4 h-4 text-accent-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 18v-5.25m0 0a6.01 6.01 0 001.5-.189m-1.5.189a6.01 6.01 0 01-1.5-.189m3.75 7.478a12.06 12.06 0 01-4.5 0m3.75 2.383a14.406 14.406 0 01-3 0M14.25 18v-.192c0-.983.658-1.823 1.508-2.316a7.5 7.5 0 10-7.517 0c.85.493 1.509 1.333 1.509 2.316V18" /></svg>
                            Contoh:
                        </p>
                        <p class="mt-2 text-xs text-ink/50">Hari 01 &middot; 15:00</p>
                        <p class="text-xs text-ink/70">Setelah makan siang, kunjungi {{ $destination->name }}.</p>
                    </div>

                    <div class="mt-4">
                        <p class="flex items-center gap-1.5 text-xs font-semibold text-ink/60 mb-2">
                            <svg class="w-4 h-4 text-accent-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                            Lihat lokasi di peta
                        </p>
                        <div id="map" class="h-48 rounded-xl border border-primary-100 z-0"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related -->
        @if ($related->isNotEmpty())
            <div class="mt-16">
                <h2 class="text-2xl font-bold text-ink">Tempat Lain yang Mungkin Kamu Suka</h2>
                <div class="mt-6 grid grid-cols-2 lg:grid-cols-4 gap-5">
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
                const map = L.map('map').setView([lat, lng], 15);
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
                } else {
                    navigator.clipboard.writeText(window.location.href);
                    alert('Tautan disalin ke clipboard!');
                }
            }
        </script>
    </x-slot>
</x-app-layout>
