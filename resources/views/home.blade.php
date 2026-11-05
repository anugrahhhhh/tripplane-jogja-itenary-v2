@php
    $heroPhotos = $popular->filter(fn ($d) => $d->cover_photo)->take(3)->values();
    $totalDestinations = $categories->sum('destinations_count');
@endphp

<x-app-layout>
    <!-- Hero -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 sm:pt-8">
        <div class="relative overflow-hidden rounded-[2rem] bg-primary-900 text-white">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 pattern-dots opacity-60"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -top-32 -left-24 w-[28rem] h-[28rem] rounded-full bg-primary-500/40 blur-3xl"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -bottom-40 right-0 w-[30rem] h-[30rem] rounded-full bg-accent-500/25 blur-3xl"></div>

            <div class="relative grid grid-cols-1 lg:grid-cols-2 gap-10 lg:gap-6 items-center px-6 py-12 sm:px-12 sm:py-16 lg:py-20">
                <div class="animate-fade-up">
                    <span class="inline-flex items-center gap-2 rounded-full bg-white/10 ring-1 ring-white/15 px-3 py-1.5 text-xs font-semibold text-primary-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent-300 animate-pulse"></span>
                        @auth
                            Halo, {{ \Illuminate\Support\Str::of(auth()->user()->name)->before(' ') }}! Mau ke mana hari ini?
                        @else
                            Trip planner wisata Yogyakarta
                        @endauth
                    </span>

                    <h1 class="mt-5 text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.05] text-balance">
                        Rencanakan perjalananmu di <span class="bg-gradient-to-r from-accent-200 to-accent-400 bg-clip-text text-transparent">Yogyakarta</span>
                    </h1>
                    <p class="mt-5 text-base sm:text-lg text-primary-100/80 max-w-lg">
                        Temukan destinasi terbaik, simpan favorit, dan susun itinerary lengkap tanpa perlu bolak-balik Instagram, TikTok, atau Google.
                    </p>

                    <form action="{{ route('destinations.index') }}" method="GET" class="mt-8 flex items-center gap-2 bg-white rounded-2xl shadow-lift p-2 max-w-lg">
                        <svg class="w-5 h-5 text-ink/40 ms-2 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                        <label for="hero-search" class="sr-only">Cari destinasi</label>
                        <input id="hero-search" type="text" name="q" placeholder="Cari candi, pantai, kuliner..." class="flex-1 min-w-0 border-0 shadow-none focus:ring-0 text-sm text-ink placeholder:text-ink/40">
                        <button type="submit" class="btn-primary shrink-0 px-5">
                            Cari
                        </button>
                    </form>

                    @if ($categories->isNotEmpty())
                        <div class="mt-5 flex flex-wrap items-center gap-2">
                            <span class="text-xs text-primary-200/70">Populer:</span>
                            @foreach ($categories->sortByDesc('destinations_count')->take(4) as $category)
                                <a href="{{ route('destinations.by-category', $category) }}" class="inline-flex items-center gap-1.5 rounded-full bg-white/10 hover:bg-white/20 ring-1 ring-white/15 px-3 py-1 text-xs font-medium text-white transition">
                                    <x-category-icon :slug="$category->slug" class="w-3.5 h-3.5" />
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <dl class="mt-10 grid grid-cols-3 gap-4 max-w-md">
                        <div>
                            <dt class="text-xs text-primary-200/70">Destinasi</dt>
                            <dd class="mt-1 font-display text-2xl font-extrabold">{{ $totalDestinations }}+</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-primary-200/70">Kategori</dt>
                            <dd class="mt-1 font-display text-2xl font-extrabold">{{ $categories->count() }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs text-primary-200/70">Itinerary</dt>
                            <dd class="mt-1 font-display text-2xl font-extrabold">Gratis</dd>
                        </div>
                    </dl>
                </div>

                <!-- Kolase foto -->
                <div class="relative h-80 sm:h-[26rem] lg:h-[30rem]">
                    @if ($heroPhotos->isNotEmpty())
                        <div class="absolute right-0 top-0 w-[68%] h-[78%] rounded-3xl overflow-hidden shadow-lift ring-4 ring-white/10 rotate-2">
                            <img src="{{ asset('storage/'.$heroPhotos[0]->cover_photo) }}" alt="{{ $heroPhotos[0]->name }}" class="w-full h-full object-cover" fetchpriority="high" decoding="async">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                            <p class="absolute bottom-4 left-4 right-4 text-sm font-semibold">{{ $heroPhotos[0]->name }}</p>
                        </div>
                        @if ($heroPhotos->count() > 1)
                            <div class="absolute left-0 bottom-0 w-[52%] h-[58%] rounded-3xl overflow-hidden shadow-lift ring-4 ring-primary-900 -rotate-3">
                                <img src="{{ asset('storage/'.$heroPhotos[1]->cover_photo) }}" alt="{{ $heroPhotos[1]->name }}" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/50 to-transparent"></div>
                                <p class="absolute bottom-3 left-3 right-3 text-xs font-semibold">{{ $heroPhotos[1]->name }}</p>
                            </div>
                        @endif
                    @else
                        <div class="absolute inset-0 rounded-3xl bg-gradient-to-br from-primary-600 via-primary-500 to-accent-400 shadow-lift overflow-hidden">
                            <svg class="absolute inset-0 w-full h-full opacity-20" viewBox="0 0 400 300" fill="none" preserveAspectRatio="xMidYMid slice">
                                <circle cx="60" cy="240" r="140" stroke="white" stroke-width="1.5"/>
                                <circle cx="360" cy="40" r="100" stroke="white" stroke-width="1.5"/>
                                <circle cx="340" cy="260" r="60" stroke="white" stroke-width="1.5"/>
                            </svg>
                            <div class="absolute inset-0 flex items-center justify-center">
                                <x-brand dark size="lg" />
                            </div>
                        </div>
                    @endif

                    <!-- Kartu mengambang -->
                    <div class="absolute left-2 sm:left-6 top-6 rounded-2xl bg-white text-ink shadow-lift px-4 py-3 flex items-center gap-3 animate-fade-up" style="animation-delay: .2s">
                        <span class="w-10 h-10 rounded-xl bg-accent-100 text-accent-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z" /></svg>
                        </span>
                        <div>
                            <p class="text-xs text-ink/50">Simpan favorit</p>
                            <p class="text-sm font-bold">Satu ketukan</p>
                        </div>
                    </div>
                    <div class="absolute right-2 sm:right-4 bottom-4 rounded-2xl bg-white text-ink shadow-lift px-4 py-3 flex items-center gap-3 animate-fade-up" style="animation-delay: .35s">
                        <span class="w-10 h-10 rounded-xl bg-primary-100 text-primary-700 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                        </span>
                        <div>
                            <p class="text-xs text-ink/50">Itinerary AI</p>
                            <p class="text-sm font-bold">Siap dalam detik</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Kategori -->
    @if ($categories->isNotEmpty())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16">
            <div class="flex items-end justify-between gap-4 flex-wrap">
                <div>
                    <p class="eyebrow">Jelajahi</p>
                    <h2 class="mt-2 section-title">Mau liburan seperti apa?</h2>
                </div>
                <a href="{{ route('destinations.index') }}" class="link text-sm">Lihat katalog &rarr;</a>
            </div>

            <div class="mt-6 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                @foreach ($categories as $category)
                    <a href="{{ route('destinations.by-category', $category) }}" class="group card card-hover p-4 sm:p-5 flex items-center gap-3">
                        <span class="icon-tile w-12 h-12 group-hover:bg-primary group-hover:text-white group-hover:ring-primary transition">
                            <x-category-icon :slug="$category->slug" class="w-6 h-6" />
                        </span>
                        <span class="min-w-0">
                            <span class="block font-semibold text-ink text-sm sm:text-base leading-tight">{{ $category->name }}</span>
                            <span class="block mt-0.5 text-xs text-ink/50">{{ $category->destinations_count }} destinasi</span>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <!-- Rekomendasi -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16">
        <div class="flex items-end justify-between gap-4 flex-wrap">
            <div>
                <p class="eyebrow">Pilihan terbaik</p>
                <h2 class="mt-2 section-title">Recommended for You</h2>
                <p class="mt-1 text-sm text-ink/60">Destinasi dengan rating tertinggi dari para traveler.</p>
            </div>
            <a href="{{ route('destinations.index') }}" class="link text-sm">Lihat semua &rarr;</a>
        </div>

        <div class="mt-6 -mx-4 px-4 flex gap-5 overflow-x-auto pb-4 snap-x snap-mandatory scrollbar-none sm:mx-0 sm:px-0 sm:grid sm:grid-cols-2 lg:grid-cols-4 sm:overflow-visible">
            @forelse ($popular as $destination)
                <div class="w-64 shrink-0 snap-start sm:w-auto">
                    <x-destination-card :destination="$destination" />
                </div>
            @empty
                <div class="empty-state col-span-full w-full">
                    <p class="text-sm text-ink/50">Belum ada destinasi yang bisa ditampilkan.</p>
                </div>
            @endforelse
        </div>
    </section>

    <!-- Di Sekitarmu -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16" x-data="nearbyPreview()">
        <div class="card p-6 sm:p-8 overflow-hidden relative">
            <div aria-hidden="true" class="pointer-events-none absolute -right-20 -top-20 w-64 h-64 rounded-full bg-primary-50"></div>
            <div class="relative flex items-start justify-between flex-wrap gap-4">
                <div class="flex items-start gap-4">
                    <span class="icon-tile w-12 h-12">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                    </span>
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold text-ink">Di Sekitarmu</h2>
                        <p class="text-sm text-ink/60 mt-1 max-w-lg">Aktifkan lokasi untuk melihat destinasi terdekat dari posisimu sekarang.</p>
                    </div>
                </div>
                <button @click="locate()" type="button" :disabled="loading" class="btn-primary shrink-0">
                    <svg class="w-4 h-4" :class="loading && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 2v3m0 14v3M2 12h3m14 0h3" />
                    </svg>
                    <span x-text="loading ? 'Mencari lokasimu...' : 'Aktifkan Lokasi Saya'"></span>
                </button>
            </div>

            <div class="relative mt-6">
                <template x-if="error">
                    <p class="alert-error" x-text="error"></p>
                </template>

                <template x-if="!started">
                    <p class="text-sm text-ink/50">Klik &quot;Aktifkan Lokasi Saya&quot; untuk melihat rekomendasi di sekitarmu, atau jelajahi lewat <a href="{{ route('nearby.index') }}" class="link">halaman Rekomendasi Terdekat</a>.</p>
                </template>

                <template x-if="started && !error && items.length === 0">
                    <p class="text-sm text-ink/50">Belum ada destinasi dalam radius 5 km. Coba <a href="{{ route('nearby.index') }}" class="link">pilih titik lain di peta</a>.</p>
                </template>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" x-show="items.length > 0">
                    <template x-for="item in items" :key="item.id">
                        <a :href="'/destinasi/' + item.slug" class="group block rounded-2xl border border-primary-100 bg-cream/60 p-4 hover:bg-white hover:shadow-lift hover:-translate-y-0.5 transition">
                            <span class="badge-primary" x-text="item.category"></span>
                            <h3 class="mt-2 font-semibold text-ink group-hover:text-primary-700" x-text="item.name"></h3>
                            <p class="mt-2 flex items-center gap-1 text-xs text-ink/50">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                <span x-text="item.distance_km !== null ? item.distance_km + ' km dari lokasimu' : 'Jarak segera tersedia'"></span>
                            </p>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </section>

    <!-- Cara kerja -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16">
        <div class="text-center max-w-2xl mx-auto">
            <p class="eyebrow justify-center">Cara kerja</p>
            <h2 class="mt-2 section-title">Tiga langkah menuju liburan tanpa ribet</h2>
        </div>

        <div class="mt-10 grid grid-cols-1 md:grid-cols-3 gap-5">
            @foreach ([
                ['no' => '01', 'title' => 'Jelajahi destinasi', 'text' => 'Telusuri katalog per kategori, lihat rating, jam buka, dan harga tiket.', 'icon' => 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z'],
                ['no' => '02', 'title' => 'Simpan favorit', 'text' => 'Tandai tempat yang menarik agar mudah ditemukan saat menyusun rencana.', 'icon' => 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'],
                ['no' => '03', 'title' => 'Susun itinerary', 'text' => 'Atur jadwal per hari secara manual atau biarkan AI menyusunkannya untukmu.', 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
            ] as $step)
                <div class="card p-6 relative overflow-hidden">
                    <span aria-hidden="true" class="absolute -right-2 -top-4 font-display text-7xl font-extrabold text-primary-50 select-none">{{ $step['no'] }}</span>
                    <span class="relative icon-tile w-12 h-12">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $step['icon'] }}" /></svg>
                    </span>
                    <h3 class="relative mt-4 text-lg font-bold text-ink">{{ $step['title'] }}</h3>
                    <p class="relative mt-1.5 text-sm text-ink/60 leading-relaxed">{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    <!-- Banner AI -->
    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-16">
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-accent-100 via-accent-50 to-primary-50 ring-1 ring-accent-200 px-6 py-10 sm:px-12 grid grid-cols-1 md:grid-cols-[1fr_auto] gap-6 items-center">
            <div aria-hidden="true" class="pointer-events-none absolute -right-10 -bottom-16 w-64 h-64 rounded-full bg-accent-200/50 blur-2xl"></div>
            <div class="relative">
                <span class="badge-accent">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                    Rekomendasi AI
                </span>
                <h2 class="mt-3 text-2xl sm:text-3xl font-bold text-ink">Bingung mulai dari mana?</h2>
                <p class="mt-2 text-ink/60 max-w-xl">Ceritakan minat, budget, dan durasimu &mdash; AI akan memilihkan destinasi yang paling cocok lengkap dengan alasannya.</p>
            </div>
            <a href="{{ route('recommendations.create') }}" class="relative btn-accent btn-lg shrink-0">
                Coba Sekarang
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5L21 12m0 0l-7.5 7.5M21 12H3" /></svg>
            </a>
        </div>
    </section>

    <x-slot name="head">
        <script>
            function nearbyPreview() {
                return {
                    loading: false,
                    started: false,
                    error: null,
                    items: [],
                    locate() {
                        if (! navigator.geolocation) {
                            this.error = 'Browser tidak mendukung geolokasi.';
                            return;
                        }

                        this.loading = true;
                        this.error = null;

                        navigator.geolocation.getCurrentPosition((position) => {
                            this.started = true;
                            this.loading = false;
                            const params = new URLSearchParams({
                                lat: position.coords.latitude,
                                lng: position.coords.longitude,
                                radius: 5,
                            });

                            fetch(`{{ route('nearby.search') }}?${params}`, {
                                headers: { 'Accept': 'application/json' },
                            })
                                .then((res) => res.json())
                                .then((json) => {
                                    this.items = (json.data || []).slice(0, 4);
                                })
                                .catch(() => {
                                    this.error = 'Gagal memuat destinasi terdekat. Coba lagi sebentar.';
                                });
                        }, () => {
                            this.loading = false;
                            this.started = true;
                            this.error = 'Izin lokasi ditolak atau tidak tersedia. Coba lewat halaman Rekomendasi Terdekat untuk memilih titik manual di peta.';
                        });
                    },
                };
            }
        </script>
    </x-slot>
</x-app-layout>
