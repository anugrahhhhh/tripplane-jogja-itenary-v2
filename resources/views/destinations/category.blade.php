<x-app-layout>
    <x-slot name="header">
        <nav class="flex items-center gap-1.5 text-sm text-ink/50" aria-label="Breadcrumb">
            <a href="{{ route('destinations.index') }}" class="hover:text-primary-700">Katalog</a>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-ink">{{ $activeCategory->name }}</span>
        </nav>

        <div class="mt-4 flex items-end justify-between flex-wrap gap-5">
            <div class="flex items-center gap-4">
                <span class="w-14 h-14 rounded-2xl bg-white shadow-soft ring-1 ring-primary-100 flex items-center justify-center text-primary-600">
                    <x-category-icon :slug="$activeCategory->slug" class="w-7 h-7" />
                </span>
                <div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-ink">{{ $activeCategory->name }}</h1>
                    <p class="mt-1 text-sm text-ink/60">{{ $destinations->total() }} destinasi</p>
                </div>
            </div>

            <form action="{{ route('destinations.by-category', $activeCategory) }}" method="GET" class="flex flex-col sm:flex-row sm:items-center gap-2 w-full sm:w-auto">
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-ink/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <label for="category-search" class="sr-only">Cari di kategori ini</label>
                    <input id="category-search" type="text" name="q" value="{{ request('q') }}" placeholder="Cari di kategori ini..." class="input pl-9 sm:w-64">
                </div>
                <label for="category-sort" class="sr-only">Urutkan</label>
                <select id="category-sort" name="urutkan" onchange="this.form.submit()" class="input sm:w-auto">
                    <option value="rating" @selected($sort === 'rating')>Rating Tertinggi</option>
                    <option value="terbaru" @selected($sort === 'terbaru')>Terbaru</option>
                    <option value="jarak" @selected($sort === 'jarak')>Jarak Terdekat</option>
                </select>
            </form>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @forelse ($destinations as $destination)
                <x-destination-card :destination="$destination" />
            @empty
                <div class="empty-state col-span-full">
                    <span class="icon-tile w-14 h-14 mb-4">
                        <x-category-icon :slug="$activeCategory->slug" class="w-7 h-7" />
                    </span>
                    <p class="font-semibold text-ink">Belum ada destinasi di kategori ini.</p>
                    <a href="{{ route('destinations.index') }}" class="mt-5 btn-secondary">&larr; Kembali ke katalog</a>
                </div>
            @endforelse
        </div>

        <div class="mt-10">
            {{ $destinations->links() }}
        </div>
    </div>
</x-app-layout>
