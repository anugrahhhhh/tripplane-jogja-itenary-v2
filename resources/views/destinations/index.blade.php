<x-app-layout>
    <x-slot name="header">
        <p class="eyebrow">Explore Yogyakarta</p>
        <h1 class="mt-2 text-3xl sm:text-4xl font-extrabold text-ink">Katalog Destinasi</h1>
        <p class="mt-2 text-sm sm:text-base text-ink/60 max-w-xl">Temukan tempat menarik berdasarkan kategori, lalu pilih destinasi untuk melihat informasi lebih lengkap.</p>

        <div x-data="{ q: {{ \Illuminate\Support\Js::from(request('q', '')) }}, filterOpen: false }" class="mt-6 max-w-3xl">
            <div class="flex flex-col sm:flex-row gap-3">
                <form action="{{ route('destinations.index') }}" method="GET" class="flex-1 flex items-center gap-2 bg-white rounded-2xl shadow-soft ring-1 ring-primary-100 p-2 focus-within:ring-2 focus-within:ring-primary-300 transition">
                    <svg class="w-5 h-5 text-ink/40 ms-2 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                    <label for="catalog-search" class="sr-only">Cari destinasi</label>
                    <input id="catalog-search" type="text" name="q" x-model="q" placeholder="Cari nama tempat, restoran, atau destinasi..." class="flex-1 min-w-0 border-0 shadow-none bg-transparent px-1 focus:ring-0 text-sm">
                    @if (request('kategori'))
                        <input type="hidden" name="kategori" value="{{ request('kategori') }}">
                    @endif
                    @if (request('urutkan'))
                        <input type="hidden" name="urutkan" value="{{ request('urutkan') }}">
                    @endif
                    <button type="submit" class="btn-primary shrink-0 px-5">Cari</button>
                </form>

                <div class="relative shrink-0">
                    <button type="button" @click="filterOpen = ! filterOpen" :aria-expanded="filterOpen" class="btn-secondary h-full w-full sm:w-auto py-3.5 rounded-2xl">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m9 12h3.75M10.5 18a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 18H7.5m9-6h3.75M13.5 12a1.5 1.5 0 10-3 0m3 0a1.5 1.5 0 11-3 0M3.75 12H10.5" /></svg>
                        Filter
                        @if (request('kategori') || request('urutkan'))
                            <span class="w-2 h-2 rounded-full bg-accent-500"></span>
                        @endif
                    </button>
                    <div x-show="filterOpen" x-cloak @click.outside="filterOpen = false"
                         x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                         class="absolute right-0 mt-2 w-72 origin-top-right rounded-2xl border border-primary-100 bg-white shadow-lift p-4 z-20">
                        <form action="{{ route('destinations.index') }}" method="GET" class="space-y-3">
                            <input type="hidden" name="q" x-model="q">
                            <div>
                                <label for="filter-kategori" class="block text-xs font-bold text-ink/50 uppercase tracking-wide mb-1.5">Kategori</label>
                                <select id="filter-kategori" name="kategori" class="input">
                                    <option value="">Semua Kategori</option>
                                    @foreach ($allCategories as $category)
                                        <option value="{{ $category->slug }}" @selected(request('kategori') === $category->slug)>{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="filter-urutkan" class="block text-xs font-bold text-ink/50 uppercase tracking-wide mb-1.5">Urutkan</label>
                                <select id="filter-urutkan" name="urutkan" class="input">
                                    <option value="rating" @selected(request('urutkan', 'rating') === 'rating')>Rating Tertinggi</option>
                                    <option value="terbaru" @selected(request('urutkan') === 'terbaru')>Terbaru</option>
                                    <option value="jarak" @selected(request('urutkan') === 'jarak')>Jarak Terdekat</option>
                                </select>
                            </div>
                            <button type="submit" class="btn-primary w-full">Terapkan Filter</button>
                        </form>
                    </div>
                </div>
            </div>

            @if (request('kategori') || request('urutkan'))
                <div class="mt-3 flex flex-wrap items-center gap-2">
                    <span class="text-xs text-ink/40">Filter aktif:</span>
                    @if (request('kategori'))
                        <span class="badge-primary">
                            {{ $allCategories->firstWhere('slug', request('kategori'))?->name ?? request('kategori') }}
                        </span>
                    @endif
                    @if (request('urutkan') && request('urutkan') !== 'rating')
                        <span class="badge-primary">
                            Urut: {{ request('urutkan') === 'terbaru' ? 'Terbaru' : 'Jarak Terdekat' }}
                        </span>
                    @endif
                    <a href="{{ route('destinations.index', array_filter(['q' => request('q')])) }}" class="text-xs font-semibold text-accent-500 hover:underline">
                        Hapus filter &times;
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if ($mode === 'browse')
            <!-- Pintasan kategori -->
            <div class="-mx-4 px-4 flex gap-2 overflow-x-auto scrollbar-none pb-2 sm:mx-0 sm:px-0 sm:flex-wrap">
                @foreach ($categoryGroups as $category)
                    @if ($category->destinations->isNotEmpty())
                        <a href="#kategori-{{ $category->slug }}" class="shrink-0 inline-flex items-center gap-2 rounded-full bg-white ring-1 ring-primary-100 px-4 py-2 text-sm font-medium text-ink/70 hover:ring-primary-300 hover:text-primary-700 shadow-sm transition">
                            <x-category-icon :slug="$category->slug" class="w-4 h-4 text-primary-500" />
                            {{ $category->name }}
                            <span class="text-xs text-ink/40">{{ $category->destinations_count }}</span>
                        </a>
                    @endif
                @endforeach
            </div>

            <div class="mt-8 space-y-12">
                @foreach ($categoryGroups as $category)
                    @if ($category->destinations->isNotEmpty())
                        <section id="kategori-{{ $category->slug }}" class="scroll-mt-28">
                            <div class="flex items-end justify-between gap-3">
                                <div class="flex items-center gap-3">
                                    <span class="icon-tile w-11 h-11">
                                        <x-category-icon :slug="$category->slug" class="w-5 h-5" />
                                    </span>
                                    <div>
                                        <h2 class="text-xl font-bold text-ink">{{ $category->name }}</h2>
                                        <p class="text-xs text-ink/50">{{ $category->destinations_count }} destinasi</p>
                                    </div>
                                </div>
                                <a href="{{ route('destinations.by-category', $category) }}" class="btn-secondary btn-sm shrink-0">
                                    Lainnya &rarr;
                                </a>
                            </div>
                            <div class="mt-5 grid grid-cols-1 min-[440px]:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                                @foreach ($category->destinations as $destination)
                                    <x-destination-card :destination="$destination" />
                                @endforeach
                            </div>
                        </section>
                    @endif
                @endforeach
            </div>
        @else
            <div class="flex items-center justify-between flex-wrap gap-3">
                <p class="text-sm text-ink/60"><span class="font-bold text-ink">{{ $destinations->total() }}</span> destinasi ditemukan{{ request('q') ? ' untuk "'.request('q').'"' : '' }}.</p>
                <a href="{{ route('destinations.index') }}" class="btn-secondary btn-sm">
                    &larr; Kembali jelajahi kategori
                </a>
            </div>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                @forelse ($destinations as $destination)
                    <x-destination-card :destination="$destination" />
                @empty
                    <div class="empty-state col-span-full">
                        <span class="icon-tile w-14 h-14 mb-4">
                            <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                        </span>
                        <p class="font-semibold text-ink">Tidak ada destinasi yang cocok dengan pencarianmu.</p>
                        <p class="mt-1 text-sm text-ink/50">Coba kata kunci lain atau hapus filter yang aktif.</p>
                        <a href="{{ route('destinations.index') }}" class="mt-5 btn-primary">Lihat semua destinasi</a>
                    </div>
                @endforelse
            </div>

            <div class="mt-10">
                {{ $destinations->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
