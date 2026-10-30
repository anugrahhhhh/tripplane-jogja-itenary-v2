<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="sharedItinerary()">
        <h1 class="text-2xl sm:text-3xl font-bold text-ink">{{ $itinerary->title }}</h1>
        <p class="mt-1 text-sm text-ink/60">
            {{ $itinerary->start_date->translatedFormat('d F Y') }} &mdash; {{ $itinerary->end_date->translatedFormat('d F Y') }}
            &middot; {{ $itinerary->totalDays() }} Hari
            &middot; {{ $itinerary->traveler_count }} peserta
        </p>

        <div class="mt-6">
            @include('itineraries.partials.cost-summary', ['cost' => $cost])
        </div>

        <div class="mt-6 flex gap-2 overflow-x-auto pb-1">
            @foreach ($itinerary->itemsByDay() as $day => $items)
                <button type="button" @click="activeDay = {{ $day }}; renderDay();"
                        :class="activeDay === {{ $day }} ? 'bg-primary text-white' : 'bg-white border border-primary-100 text-ink/70 hover:bg-primary-50'"
                        class="shrink-0 rounded-xl px-4 py-2.5 text-left transition">
                    <span class="block text-xs font-bold tracking-wide uppercase">Day {{ $day }}</span>
                    <span class="block text-xs opacity-80">{{ $itinerary->start_date->copy()->addDays($day - 1)->translatedFormat('d M') }}</span>
                </button>
            @endforeach
        </div>

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
                                <li class="relative pl-8 pb-6">
                                    <span class="absolute left-0 top-1 w-[15px] h-[15px] rounded-full bg-primary border-2 border-white ring-2 ring-primary-100"></span>

                                    @if (isset($travelInfoByDay[$day][$item->id]))
                                        <p class="text-xs text-ink/40 mb-2">
                                            &plusmn;{{ $travelInfoByDay[$day][$item->id]['distance_km'] }} km &middot; sekitar {{ $travelInfoByDay[$day][$item->id]['duration_minutes'] }} menit perjalanan
                                        </p>
                                    @endif

                                    <div class="bg-white border {{ ! empty($warningsByDay[$day][$item->id] ?? []) ? 'border-amber-300' : 'border-primary-100' }} rounded-xl p-4">
                                        <p class="text-sm font-bold text-primary-700">{{ \Illuminate\Support\Carbon::parse($item->start_time)->format('H:i') }}</p>
                                        <a href="{{ route('destinations.show', $item->destination) }}" class="font-semibold text-ink hover:text-primary">{{ $item->destination->name }}</a>
                                        <p class="text-xs text-ink/50">Durasi {{ $item->duration_minutes }} menit</p>
                                        @foreach ($warningsByDay[$day][$item->id] ?? [] as $warning)
                                            <p class="mt-1 text-xs font-medium text-amber-600">{{ $warning }}</p>
                                        @endforeach
                                    </div>
                                </li>
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
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script>
            window.itineraryDays = @json($mapPointsByDay);

            function sharedItinerary() {
                return {
                    activeDay: 1,
                    map: null,
                    layer: null,
                    init() {
                        this.map = L.map('route-map').setView([-7.8014, 110.3644], 12);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors',
                        }).addTo(this.map);
                        this.layer = L.layerGroup().addTo(this.map);
                        this.renderDay();
                    },
                    renderDay() {
                        this.layer.clearLayers();
                        const points = window.itineraryDays[this.activeDay] || [];
                        if (points.length === 0) {
                            return;
                        }
                        const latlngs = points.map((p, idx) => {
                            L.marker([p.lat, p.lng]).bindPopup((idx + 1) + '. ' + p.name).addTo(this.layer);
                            return [p.lat, p.lng];
                        });
                        if (latlngs.length > 1) {
                            L.polyline(latlngs, { color: '#2F6B5A', weight: 3 }).addTo(this.layer);
                        }
                        this.map.fitBounds(latlngs, { padding: [30, 30] });
                    },
                };
            }
        </script>
    </x-slot>
</x-app-layout>
