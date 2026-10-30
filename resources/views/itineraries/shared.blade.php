<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="itineraryMap()">
        <h1 class="text-2xl sm:text-3xl font-bold text-ink">{{ $itinerary->title }}</h1>
        <p class="mt-1 text-sm text-ink/60">
            {{ $itinerary->start_date->translatedFormat('d F Y') }} &mdash; {{ $itinerary->end_date->translatedFormat('d F Y') }}
            &middot; {{ $itinerary->totalDays() }} Hari
            &middot; {{ $itinerary->traveler_count }} peserta
        </p>

        <div class="mt-6">
            @include('itineraries.partials.cost-summary', ['cost' => $cost])
        </div>

        @include('itineraries.partials.day-tabs', ['itinerary' => $itinerary])

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                @foreach ($itinerary->itemsByDay() as $day => $items)
                    <div x-show="activeDay === {{ $day }}" x-cloak>
                        <h2 class="font-semibold text-ink">Day {{ $day }} &mdash; {{ $itinerary->start_date->copy()->addDays($day - 1)->translatedFormat('d F Y') }}</h2>
                        <p class="text-xs text-ink/50">{{ $items->count() }} destinasi</p>

                        <ol class="mt-5 relative">
                            @if ($items->isNotEmpty())
                                <div class="absolute left-[7px] top-2 bottom-2 w-px bg-primary-100"></div>
                            @endif

                            @forelse ($items as $item)
                                @include('itineraries.partials.shared-item', ['item' => $item, 'day' => $day, 'travelInfoByDay' => $travelInfoByDay, 'warningsByDay' => $warningsByDay])
                            @empty
                                <li class="pl-8 text-sm text-ink/50">Belum ada destinasi di hari ini.</li>
                            @endforelse
                        </ol>
                    </div>
                @endforeach
            </div>

            <div>
                <div class="bg-white border border-primary-100 rounded-2xl p-4">
                    <h3 class="font-semibold text-ink text-sm">Peta Rute &mdash; Day <span x-text="activeDay"></span></h3>
                    <div id="route-map" class="mt-3 h-64 rounded-xl z-0"></div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="head">
        @include('itineraries.partials.route-map', ['mapPointsByDay' => $mapPointsByDay])
    </x-slot>
</x-app-layout>
