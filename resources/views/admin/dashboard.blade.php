@php
    $stats = [
        ['label' => 'Total Destinasi', 'value' => $totalDestinations, 'route' => route('admin.destinations.index'), 'tone' => 'primary', 'icon' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z'],
        ['label' => 'Total Kategori', 'value' => $totalCategories, 'route' => route('admin.categories.index'), 'tone' => 'primary', 'icon' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z M6 6h.008v.008H6V6z'],
        ['label' => 'Traveler Terdaftar', 'value' => $totalTravelers, 'route' => route('admin.users.index'), 'tone' => 'primary', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z'],
        ['label' => 'Total Itinerary', 'value' => $totalItineraries, 'route' => null, 'tone' => 'primary', 'icon' => 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
        ['label' => 'Total Ulasan', 'value' => $totalReviews, 'route' => route('admin.reviews.index'), 'tone' => 'primary', 'icon' => 'M11.48 3.499a.562.562 0 011.04 0l2.125 5.111a.563.563 0 00.475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 00-.182.557l1.285 5.385a.562.562 0 01-.84.61l-4.725-2.885a.563.563 0 00-.586 0L6.982 20.54a.562.562 0 01-.84-.61l1.285-5.386a.562.562 0 00-.182-.557l-4.204-3.602a.563.563 0 01.321-.988l5.518-.442a.563.563 0 00.475-.345L11.48 3.5z'],
        ['label' => 'Ulasan Disembunyikan', 'value' => $pendingReviews, 'route' => route('admin.reviews.index', ['status' => 'sembunyi']), 'tone' => 'accent', 'icon' => 'M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88'],
    ];
    $maxPerCategory = max(1, (int) $destinationsPerCategory->max('destinations_count'));
@endphp

<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-ink">Dashboard Admin</h1>
                <p class="text-sm text-ink/60 mt-1">Ringkasan statistik JogjaTrip.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.destinations.create') }}" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Tambah Destinasi
                </a>
                <a href="{{ route('admin.categories.create') }}" class="btn-secondary">Tambah Kategori</a>
            </div>
        </div>
    </x-slot>

    <div class="grid grid-cols-2 lg:grid-cols-3 2xl:grid-cols-6 gap-4">
        @foreach ($stats as $stat)
            @php $tag = $stat['route'] ? 'a' : 'div'; @endphp
            <{{ $tag }} @if ($stat['route']) href="{{ $stat['route'] }}" @endif
                class="group relative overflow-hidden rounded-2xl p-5 shadow-soft transition {{ $stat['tone'] === 'accent' ? 'bg-gradient-to-br from-accent-50 to-accent-100 ring-1 ring-accent-200' : 'bg-white ring-1 ring-primary-100/70' }} {{ $stat['route'] ? 'hover:shadow-lift hover:-translate-y-0.5' : '' }}">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center {{ $stat['tone'] === 'accent' ? 'bg-white text-accent-600' : 'bg-primary-50 text-primary-600' }}">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $stat['icon'] }}" /></svg>
                </span>
                <p class="mt-4 text-xs font-semibold {{ $stat['tone'] === 'accent' ? 'text-accent-600' : 'text-ink/50' }}">{{ $stat['label'] }}</p>
                <p class="mt-1 font-display text-3xl font-extrabold {{ $stat['tone'] === 'accent' ? 'text-primary-800' : 'text-ink' }}">{{ number_format($stat['value']) }}</p>
                @if ($stat['route'])
                    <svg class="absolute top-5 right-5 w-4 h-4 text-ink/20 group-hover:text-primary-500 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 19.5l15-15m0 0H8.25m11.25 0v11.25" /></svg>
                @endif
            </{{ $tag }}>
        @endforeach
    </div>

    <div class="mt-8 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-ink">Destinasi per Kategori</h2>
                <a href="{{ route('admin.categories.index') }}" class="text-xs font-semibold text-accent-500 hover:underline">Kelola &rarr;</a>
            </div>
            <ul class="mt-5 space-y-4">
                @foreach ($destinationsPerCategory as $category)
                    <li>
                        <div class="flex items-center justify-between text-sm">
                            <span class="flex items-center gap-2 text-ink/70">
                                <x-category-icon :slug="$category->slug" class="w-4 h-4 text-primary-500" />
                                {{ $category->name }}
                            </span>
                            <span class="font-bold text-ink">{{ $category->destinations_count }}</span>
                        </div>
                        <div class="mt-1.5 h-2 rounded-full bg-primary-50 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-primary-400 to-primary-600" style="width: {{ round($category->destinations_count / $maxPerCategory * 100) }}%"></div>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card p-6">
            <div class="flex items-center justify-between">
                <h2 class="font-bold text-ink">Ulasan Terbaru</h2>
                <a href="{{ route('admin.reviews.index') }}" class="text-xs font-semibold text-accent-500 hover:underline">Moderasi &rarr;</a>
            </div>
            <ul class="mt-5 divide-y divide-primary-50">
                @forelse ($latestReviews as $review)
                    <li class="py-3 first:pt-0 flex items-start gap-3">
                        <span class="w-9 h-9 shrink-0 rounded-full bg-gradient-to-br from-primary-100 to-accent-100 text-primary-700 flex items-center justify-center text-xs font-bold">
                            {{ strtoupper(substr($review->user->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-ink truncate">{{ $review->user->name }} &rarr; {{ $review->destination->name }}</p>
                            <p class="mt-0.5 flex items-center gap-1.5 text-xs text-ink/50">
                                <span class="inline-flex items-center gap-0.5 font-semibold text-amber-600">
                                    <svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.957a1 1 0 00-.363-1.118L2.062 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" /></svg>
                                    Rating {{ $review->rating }}/5
                                </span>
                                &middot; {{ $review->created_at->diffForHumans() }}
                            </p>
                        </div>
                        @unless ($review->is_visible)
                            <span class="badge-danger shrink-0">Disembunyikan</span>
                        @endunless
                    </li>
                @empty
                    <li class="py-8 text-sm text-ink/50 text-center">Belum ada ulasan.</li>
                @endforelse
            </ul>
        </div>
    </div>

    <div class="mt-10 flex items-center gap-3">
        <h2 class="font-bold text-ink text-lg">Analitik</h2>
        <div class="flex-1 h-px bg-primary-100"></div>
    </div>
    <div class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card p-6">
            <h3 class="font-bold text-ink text-sm">Kategori Terpopuler</h3>
            <p class="text-xs text-ink/50">Berdasarkan total favorit destinasi di kategori itu.</p>
            @if ($categoryPopularity->sum('favorites_count') > 0)
                <div class="mt-4 h-64"><canvas id="chart-category-popularity"></canvas></div>
            @else
                <p class="mt-6 text-sm text-ink/40 text-center py-10 rounded-xl bg-cream/60">Belum ada data favorit.</p>
            @endif
        </div>

        <div class="card p-6">
            <h3 class="font-bold text-ink text-sm">Destinasi Paling Difavoritkan</h3>
            <p class="text-xs text-ink/50">Top {{ $topFavoritedDestinations->count() }} destinasi berdasarkan jumlah favorit.</p>
            @if ($topFavoritedDestinations->isNotEmpty())
                <div class="mt-4 h-64"><canvas id="chart-top-favorited"></canvas></div>
            @else
                <p class="mt-6 text-sm text-ink/40 text-center py-10 rounded-xl bg-cream/60">Belum ada destinasi yang difavoritkan.</p>
            @endif
        </div>

        <div class="card p-6 lg:col-span-2">
            <h3 class="font-bold text-ink text-sm">Tren Pembuatan Itinerary</h3>
            <p class="text-xs text-ink/50">Jumlah itinerary baru per bulan, 6 bulan terakhir.</p>
            <div class="mt-4 h-64"><canvas id="chart-itinerary-trend"></canvas></div>
        </div>
    </div>

    <x-slot name="head">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.4/chart.umd.min.js"></script>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const inkMuted = '#3A3A3A66';
                const gridColor = "#2F6B5A14";
                Chart.defaults.font.family = "Figtree, sans-serif";

                const baseOptions = {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { grid: { color: gridColor }, ticks: { color: inkMuted, font: { size: 11 } } },
                        y: { grid: { display: false }, ticks: { color: inkMuted, font: { size: 11 } } },
                    },
                };

                @if ($categoryPopularity->sum('favorites_count') > 0)
                    new Chart(document.getElementById('chart-category-popularity'), {
                        type: 'bar',
                        data: {
                            labels: {{ Illuminate\Support\Js::from($categoryPopularity->pluck('name')) }},
                            datasets: [{
                                data: {{ Illuminate\Support\Js::from($categoryPopularity->pluck('favorites_count')) }},
                                backgroundColor: '#2F6B5A',
                                borderRadius: 8, borderSkipped: false,
                                barThickness: 18,
                            }],
                        },
                        options: { ...baseOptions, indexAxis: 'y' },
                    });
                @endif

                @if ($topFavoritedDestinations->isNotEmpty())
                    new Chart(document.getElementById('chart-top-favorited'), {
                        type: 'bar',
                        data: {
                            labels: {{ Illuminate\Support\Js::from($topFavoritedDestinations->pluck('name')) }},
                            datasets: [{
                                data: {{ Illuminate\Support\Js::from($topFavoritedDestinations->pluck('favorites_count')) }},
                                backgroundColor: '#2F6B5A',
                                borderRadius: 8, borderSkipped: false,
                                barThickness: 14,
                            }],
                        },
                        options: { ...baseOptions, indexAxis: 'y' },
                    });
                @endif

                new Chart(document.getElementById('chart-itinerary-trend'), {
                    type: 'bar',
                    data: {
                        labels: {{ Illuminate\Support\Js::from(collect($monthlyItineraryTrend)->pluck('label')) }},
                        datasets: [{
                            data: {{ Illuminate\Support\Js::from(collect($monthlyItineraryTrend)->pluck('total')) }},
                            backgroundColor: '#B56E80',
                            borderRadius: 8, borderSkipped: false,
                            barThickness: 32,
                        }],
                    },
                    options: baseOptions,
                });
            });
        </script>
    </x-slot>
</x-admin-layout>
