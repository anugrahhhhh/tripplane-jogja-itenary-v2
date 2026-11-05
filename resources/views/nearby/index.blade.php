<x-app-layout>
    <x-slot name="header">
        <p class="eyebrow">Di sekitarmu</p>
        <h1 class="mt-2 text-3xl sm:text-4xl font-extrabold text-ink">Rekomendasi Terdekat</h1>
        <p class="text-sm text-ink/60 mt-2">Aktifkan lokasi, atau klik titik di peta untuk memilih posisi manual.</p>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="nearbyPage()" x-init="init()">
        <div class="card p-4 flex flex-wrap items-center gap-3">
            <button type="button" @click="locateMe()" :disabled="loading" class="btn-primary">
                <svg class="w-4 h-4" :class="loading && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v3m0 14v3M2 12h3m14 0h3" /></svg>
                <span x-text="loading ? 'Mencari lokasimu...' : 'Aktifkan Lokasi Saya'"></span>
            </button>

            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold text-ink/50">Radius</span>
                <div class="inline-flex rounded-xl bg-cream p-1 ring-1 ring-primary-100">
                    <template x-for="r in [1, 3, 5]" :key="r">
                        <button type="button" @click="radius = r; search();"
                                :class="radius === r ? 'bg-primary text-white shadow-sm' : 'text-primary-700 hover:bg-white'"
                                class="rounded-lg px-4 py-1.5 text-sm font-semibold transition" x-text="r + ' km'"></button>
                    </template>
                </div>
            </div>

            <p class="text-xs text-ink/50 sm:ml-auto inline-flex items-center gap-1.5" x-show="point" x-cloak>
                <span class="w-2 h-2 rounded-full bg-primary-500"></span>
                Titik terpilih: <span class="font-mono" x-text="point ? point.lat.toFixed(5) + ', ' + point.lng.toFixed(5) : ''"></span>
            </p>
        </div>

        <p class="mt-3 alert-error" x-show="error" x-cloak x-text="error"></p>

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-5 gap-6">
            <div class="lg:col-span-2">
                <div class="lg:sticky lg:top-24 card p-2">
                    <div id="nearby-map" class="h-80 sm:h-96 lg:h-[32rem] rounded-xl overflow-hidden z-0"></div>
                    <p class="px-2 pt-2 pb-1 text-xs text-ink/50">Klik di mana saja pada peta untuk mencari dari titik itu.</p>
                </div>
            </div>

            <div class="lg:col-span-3">
                <div x-show="!point" class="empty-state">
                    <span class="icon-tile w-14 h-14 mb-4">
                        <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.880 3 6.270 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" /></svg>
                    </span>
                    <p class="font-semibold text-ink">Belum ada titik lokasi dipilih.</p>
                    <p class="mt-1 text-sm text-ink/50">Klik tombol di atas atau klik langsung di peta.</p>
                </div>

                <form :action="'{{ route('today-itinerary.store') }}'" method="POST" @submit="submitToday">
                    @csrf
                    <input type="hidden" name="lat" :value="point ? point.lat : ''">
                    <input type="hidden" name="lng" :value="point ? point.lng : ''">
                    <template x-if="items.length > 0">
                        <div class="flex items-center justify-between flex-wrap gap-3 mb-4">
                            <p class="text-sm text-ink/60"><span class="font-bold text-ink" x-text="items.length"></span> destinasi ditemukan dalam radius <span x-text="radius"></span> km.</p>
                            @auth
                                <button type="submit" class="btn-accent btn-sm py-2">
                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                    Tambahkan ke Itinerary Hari Ini
                                </button>
                            @endauth
                        </div>
                    </template>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <template x-for="item in items" :key="item.id">
                            <label class="relative block card p-4 cursor-pointer hover:shadow-lift hover:border-primary-200 transition has-[:checked]:ring-2 has-[:checked]:ring-primary-400 has-[:checked]:border-primary-300">
                                @auth
                                    <input type="checkbox" name="destination_ids[]" :value="item.id" class="absolute top-4 right-4 w-5 h-5 rounded-md border-primary-300 text-primary focus:ring-primary">
                                @endauth
                                <span class="badge-primary" x-text="item.category"></span>
                                <h3 class="mt-2 font-bold text-ink pr-8" x-text="item.name"></h3>
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                    <span class="inline-flex items-center gap-1 text-ink/50">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                        <span x-text="item.distance_km !== null ? item.distance_km + ' km' : 'Jarak segera tersedia'"></span>
                                    </span>
                                    <span x-show="item.is_open !== null" :class="item.is_open ? 'badge-primary' : 'badge-danger'" x-text="item.is_open ? 'Buka sekarang' : 'Tutup sekarang'"></span>
                                </div>
                                <a :href="'/destinasi/' + item.slug" @click.stop class="mt-3 inline-flex items-center text-xs font-semibold text-accent-500 hover:underline">Lihat detail &rarr;</a>
                            </label>
                        </template>
                    </div>

                    <template x-if="!loading && point && items.length === 0">
                        <div class="empty-state">
                            <p class="font-semibold text-ink">Tidak ada destinasi ditemukan di titik ini.</p>
                            <p class="mt-1 text-sm text-ink/50">Coba perbesar radius atau pilih titik lain di peta.</p>
                        </div>
                    </template>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="head">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
        <script>
            function nearbyPage() {
                return {
                    map: null,
                    userMarker: null,
                    itemsLayer: null,
                    point: null,
                    radius: 3,
                    loading: false,
                    error: null,
                    items: [],
                    init() {
                        this.map = L.map('nearby-map').setView([-7.7972, 110.3688], 13);
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '&copy; OpenStreetMap contributors',
                        }).addTo(this.map);
                        this.itemsLayer = L.layerGroup().addTo(this.map);

                        this.map.on('click', (e) => {
                            this.setPoint(e.latlng.lat, e.latlng.lng);
                        });
                    },
                    locateMe() {
                        if (! navigator.geolocation) {
                            this.error = 'Browser tidak mendukung geolokasi.';
                            return;
                        }
                        this.loading = true;
                        this.error = null;
                        navigator.geolocation.getCurrentPosition((position) => {
                            this.setPoint(position.coords.latitude, position.coords.longitude);
                            this.map.setView([position.coords.latitude, position.coords.longitude], 14);
                        }, () => {
                            this.loading = false;
                            this.error = 'Izin lokasi ditolak. Silakan klik titik di peta untuk memilih lokasi manual.';
                        });
                    },
                    setPoint(lat, lng) {
                        this.point = { lat, lng };
                        if (this.userMarker) {
                            this.map.removeLayer(this.userMarker);
                        }
                        this.userMarker = L.marker([lat, lng], {
                            icon: L.divIcon({ className: '', html: '<div style="width:18px;height:18px;background:#2F6B5A;border:3px solid white;border-radius:50%;box-shadow:0 0 0 6px rgba(47,107,90,.25);"></div>', iconSize: [18, 18], iconAnchor: [9, 9] }),
                        }).addTo(this.map);
                        this.search();
                    },
                    search() {
                        if (! this.point) {
                            return;
                        }
                        this.loading = true;
                        const params = new URLSearchParams({ lat: this.point.lat, lng: this.point.lng, radius: this.radius });
                        fetch(`{{ route('nearby.search') }}?${params}`, { headers: { 'Accept': 'application/json' } })
                            .then((res) => res.json())
                            .then((json) => {
                                this.items = json.data || [];
                                this.loading = false;
                                this.renderMarkers();
                            })
                            .catch(() => { this.loading = false; });
                    },
                    renderMarkers() {
                        this.itemsLayer.clearLayers();
                        this.items.forEach((item) => {
                            L.marker([item.latitude, item.longitude]).bindPopup(item.name).addTo(this.itemsLayer);
                        });
                    },
                    submitToday(e) {
                        const checked = this.$el.querySelectorAll('input[name="destination_ids[]"]:checked');
                        if (checked.length === 0) {
                            e.preventDefault();
                            alert('Pilih minimal satu destinasi terlebih dahulu.');
                        }
                    },
                };
            }
        </script>
    </x-slot>
</x-app-layout>
