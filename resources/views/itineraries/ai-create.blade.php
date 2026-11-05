<x-app-layout>
    <x-slot name="header">
        <nav class="flex items-center gap-1.5 text-sm text-ink/50" aria-label="Breadcrumb">
            <a href="{{ route('itineraries.index') }}" class="hover:text-primary-700">Itinerary</a>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-ink">Susun dengan AI</span>
        </nav>
        <div class="mt-3 flex items-center gap-3">
            <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-accent-400 to-accent-600 text-white flex items-center justify-center shadow-sm">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
            </span>
            <h1 class="text-3xl font-extrabold text-ink">Susun Itinerary dengan AI</h1>
        </div>
        <p class="mt-2 text-sm text-ink/60 max-w-2xl">Kasih tahu preferensimu, AI akan susunkan itinerary multi-hari lengkap dengan urutan dan jam kunjungan.</p>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if (session('error'))
            <div class="alert-error mb-6">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <form action="{{ route('itineraries.ai-store') }}" method="POST" x-data="aiItineraryForm()" @submit="submitting = true" class="card divide-y divide-primary-50">
            @csrf
            <input type="hidden" name="lat" :value="lat">
            <input type="hidden" name="lng" :value="lng">

            <section class="p-6 sm:p-8 space-y-5">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center">1</span>
                    <h2 class="font-bold text-ink">Kapan perjalananmu?</h2>
                </div>

                <div>
                    <x-input-label for="title" value="Nama Itinerary (opsional)" />
                    <x-text-input id="title" name="title" type="text" class="mt-1.5 block w-full" value="{{ old('title') }}" placeholder="Contoh: Liburan Jogja 3 Hari" />
                    <x-input-error :messages="$errors->get('title')" class="mt-1.5" />
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <x-input-label for="start_date" value="Tanggal Mulai" />
                        <x-text-input id="start_date" name="start_date" type="date" class="mt-1.5 block w-full" value="{{ old('start_date') }}" required />
                        <x-input-error :messages="$errors->get('start_date')" class="mt-1.5" />
                    </div>
                    <div>
                        <x-input-label for="end_date" value="Tanggal Selesai" />
                        <x-text-input id="end_date" name="end_date" type="date" class="mt-1.5 block w-full" value="{{ old('end_date') }}" required />
                        <x-input-error :messages="$errors->get('end_date')" class="mt-1.5" />
                    </div>
                </div>
                <p class="text-xs text-ink/40 -mt-2">Maksimal 7 hari per itinerary buatan AI.</p>
            </section>

            <section class="p-6 sm:p-8 space-y-5">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center">2</span>
                    <h2 class="font-bold text-ink">Apa yang kamu suka?</h2>
                </div>

                <div>
                    <p class="label mb-2">Minat kategori (boleh pilih lebih dari satu)</p>
                    @include('partials.interest-picker')
                    <x-input-error :messages="$errors->get('interests')" class="mt-1.5" />
                </div>

                <div>
                    <x-input-label for="notes" value="Ceritakan lebih detail (opsional)" />
                    <textarea id="notes" name="notes" rows="3" maxlength="500" placeholder="Contoh: perjalanan santai bareng keluarga, hindari tempat yang terlalu ramai, suka wisata sejarah..."
                        class="input mt-1.5">{{ old('notes') }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-1.5" />
                </div>
            </section>

            <section class="p-6 sm:p-8 space-y-5">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center">3</span>
                    <h2 class="font-bold text-ink">Budget &amp; titik berangkat</h2>
                </div>

                <div>
                    <x-input-label for="budget_per_day" value="Budget per hari (Rp, opsional)" />
                    <div class="relative mt-1.5">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-ink/40">Rp</span>
                        <x-text-input id="budget_per_day" name="budget_per_day" type="number" min="0" step="1000" class="block w-full pl-11" value="{{ old('budget_per_day') }}" placeholder="300000" />
                    </div>
                    <x-input-error :messages="$errors->get('budget_per_day')" class="mt-1.5" />
                </div>

                <div>
                    <p class="label mb-2">Titik keberangkatan (opsional, untuk pertimbangan jarak)</p>
                    <button type="button" @click="locate()" :disabled="locating" class="btn-secondary" :class="lat && '!border-primary-300 !bg-primary-50 !text-primary-700'">
                        <svg class="w-4 h-4" :class="locating && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v3m0 14v3M2 12h3m14 0h3" /></svg>
                        <span x-text="locating ? 'Mencari lokasimu...' : (lat ? 'Lokasi berhasil dibagikan' : 'Bagikan Lokasi Saya')"></span>
                    </button>
                    <p class="mt-2 text-xs text-red-600" x-show="locError" x-text="locError"></p>
                </div>
            </section>

            <div class="p-6 sm:px-8 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3 bg-cream/50 rounded-b-2xl">
                <p class="text-xs text-ink/50" role="status" aria-live="polite" x-text="submitting ? 'AI sedang bekerja. Biasanya 5–20 detik, paling lama sekitar 1 menit. Jangan tutup halaman ini.' : 'Proses penyusunan biasanya butuh beberapa detik.'">Proses penyusunan biasanya butuh beberapa detik.</p>
                <div class="flex flex-col-reverse sm:flex-row gap-2">
                    <a href="{{ route('itineraries.index') }}" class="btn-secondary">Batal</a>
                    <button type="submit" :disabled="submitting" class="btn-primary px-6">
                        <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                        <span x-show="!submitting">Susunkan Itinerary</span>
                        <span x-show="submitting" x-cloak>Menyusun itinerary...</span>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <x-slot name="head">
        <script>
            function aiItineraryForm() {
                return {
                    lat: null,
                    lng: null,
                    locating: false,
                    locError: null,
                    submitting: false,
                    locate() {
                        if (! navigator.geolocation) {
                            this.locError = 'Browser tidak mendukung geolokasi.';
                            return;
                        }
                        this.locating = true;
                        this.locError = null;
                        navigator.geolocation.getCurrentPosition((position) => {
                            this.lat = position.coords.latitude;
                            this.lng = position.coords.longitude;
                            this.locating = false;
                        }, () => {
                            this.locating = false;
                            this.locError = 'Izin lokasi ditolak. Itinerary tetap bisa disusun tanpa lokasi.';
                        });
                    },
                };
            }
        </script>
    </x-slot>
</x-app-layout>
