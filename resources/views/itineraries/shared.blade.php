<x-app-layout>
    <x-slot name="title">{{ $itinerary->title }}</x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8" x-data="itineraryMap()">
        <div class="relative overflow-hidden rounded-[2rem] bg-primary-900 text-white px-6 py-8 sm:px-10 sm:py-10">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 pattern-dots opacity-60"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -top-24 -right-10 w-80 h-80 rounded-full bg-accent-500/25 blur-3xl"></div>
            <div class="relative">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 ring-1 ring-white/15 px-3 py-1 text-xs font-semibold text-primary-100">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                    Itinerary yang dibagikan
                </span>
                <h1 class="mt-3 text-2xl sm:text-4xl font-extrabold text-white">{{ $itinerary->title }}</h1>
                <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-primary-100/80">
                    <span>{{ $itinerary->start_date->translatedFormat('d F Y') }} &mdash; {{ $itinerary->end_date->translatedFormat('d F Y') }}</span>
                    <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-semibold">{{ $itinerary->totalDays() }} Hari</span>
                    <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-semibold">{{ $itinerary->traveler_count }} peserta</span>
                </p>
            </div>
        </div>

        <div class="mt-6">
            @include('itineraries.partials.cost-summary', ['cost' => $cost])
        </div>

        @include('itineraries.partials.day-tabs', ['itinerary' => $itinerary])

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                @foreach ($itinerary->itemsByDay() as $day => $items)
                    <div x-show="activeDay === {{ $day }}" x-cloak>
                        <div class="card px-5 py-4">
                            <h2 class="text-lg font-bold text-ink">Day {{ $day }} &mdash; {{ $itinerary->start_date->copy()->addDays($day - 1)->translatedFormat('d F Y') }}</h2>
                            <p class="text-xs text-ink/50">{{ $items->count() }} destinasi</p>
                        </div>

                        <ol class="mt-6 relative">
                            @if ($items->isNotEmpty())
                                <div class="absolute left-[15px] top-4 bottom-6 w-0.5 bg-gradient-to-b from-primary-200 via-primary-100 to-transparent"></div>
                            @endif

                            @forelse ($items as $item)
                                @include('itineraries.partials.shared-item', ['item' => $item, 'day' => $day, 'travelInfoByDay' => $travelInfoByDay, 'warningsByDay' => $warningsByDay])
                            @empty
                                <li class="text-sm text-ink/50 text-center py-4">Belum ada destinasi di hari ini.</li>
                            @endforelse
                        </ol>
                    </div>
                @endforeach
            </div>

            <div>
                <div class="lg:sticky lg:top-24 space-y-5">
                    <div class="card p-4">
                        <h3 class="font-bold text-ink text-sm">Peta Rute &mdash; Day <span x-text="activeDay"></span></h3>
                        <div id="route-map" class="mt-3 h-64 rounded-xl ring-1 ring-primary-100 overflow-hidden z-0"></div>
                    </div>
                    <div class="rounded-2xl bg-gradient-to-br from-accent-50 to-primary-50 ring-1 ring-accent-200 p-5">
                        <p class="font-bold text-ink">Suka rencana ini?</p>
                        <p class="mt-1 text-sm text-ink/60">Buat itinerary versimu sendiri di JogjaTrip &mdash; gratis.</p>
                        <a href="{{ route('destinations.index') }}" class="mt-4 btn-primary btn-sm py-2">Jelajahi destinasi</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="head">
        @include('itineraries.partials.route-map', ['mapPointsByDay' => $mapPointsByDay])
    </x-slot>
</x-app-layout>
