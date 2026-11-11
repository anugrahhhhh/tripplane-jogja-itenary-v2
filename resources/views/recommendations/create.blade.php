<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center gap-3">
            <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-accent-400 to-accent-600 text-white flex items-center justify-center shadow-sm">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" /></svg>
            </span>
            <div>
                <p class="eyebrow">Didukung AI</p>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-ink">Rekomendasi AI</h1>
            </div>
        </div>
        <p class="text-sm text-ink/60 mt-3 max-w-2xl">Ceritakan preferensimu, biar AI pilihkan destinasi yang paling cocok di Kota Yogyakarta.</p>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if (session('error'))
            <div class="alert-error mb-6">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                <p>{{ session('error') }}</p>
            </div>
        @endif

        <form action="{{ route('recommendations.store') }}" method="POST" x-data="aiRecommendationForm()" @submit="submitting = true" class="card divide-y divide-primary-50">
            @csrf
            <input type="hidden" name="lat" :value="lat">
            <input type="hidden" name="lng" :value="lng">

            <section class="p-6 sm:p-8 space-y-5">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center">1</span>
                    <h2 class="font-bold text-ink">Apa yang kamu cari?</h2>
                </div>

                <div>
                    <p class="label mb-2">Minat kategori (boleh pilih lebih dari satu)</p>
                    @include('partials.interest-picker')
                    @error('interests') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="notes" class="label mb-1.5">Ceritakan lebih detail (opsional)</label>
                    <textarea id="notes" name="notes" rows="3" maxlength="500" placeholder="Contoh: suka suasana tenang, cari yang ramah untuk anak kecil, ingin coba kuliner malam yang unik..."
                        class="input">{{ old('notes') }}</textarea>
                    @error('notes') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </section>

            <section class="p-6 sm:p-8 space-y-5">
                <div class="flex items-center gap-3">
                    <span class="w-7 h-7 rounded-full bg-primary text-white text-xs font-bold flex items-center justify-center">2</span>
                    <h2 class="font-bold text-ink">Budget, durasi &amp; lokasi</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="budget_max" class="label mb-1.5">Budget maksimal per aktivitas (Rp)</label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm font-semibold text-ink/40">Rp</span>
                            <input type="number" id="budget_max" name="budget_max" min="0" step="1000" value="{{ old('budget_max') }}" placeholder="50000" class="input pl-11">
                        </div>
                        @error('budget_max') <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="duration" class="label mb-1.5">Durasi kunjungan</label>
                        <select id="duration" name="duration" class="input">
                            <option value="">Tidak ditentukan</option>
                            <option value="singkat" @selected(old('duration') === 'singkat')>1-2 jam</option>
                            <option value="setengah-hari" @selected(old('duration') === 'setengah-hari')>Setengah hari (3-5 jam)</option>
                            <option value="seharian" @selected(old('duration') === 'seharian')>Seharian (6+ jam)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <p class="label mb-2">Lokasi saat ini (opsional, untuk pertimbangan jarak)</p>
                    <button type="button" @click="locate()" :disabled="locating" class="btn-secondary" :class="lat && '!border-primary-300 !bg-primary-50 !text-primary-700'">
                        <svg class="w-4 h-4" :class="locating && 'animate-spin'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 2v3m0 14v3M2 12h3m14 0h3" /></svg>
                        <span x-text="locating ? 'Mencari lokasimu...' : (lat ? 'Lokasi berhasil dibagikan' : 'Bagikan Lokasi Saya')"></span>
                    </button>
                    <p class="mt-2 text-xs text-red-600" x-show="locError" x-text="locError"></p>
                    <p class="mt-2 text-xs text-ink/50" x-show="lat" x-cloak>Titik: <span class="font-mono" x-text="lat + ', ' + lng"></span></p>
                </div>
            </section>

            <div class="p-6 sm:px-8 bg-cream/50 rounded-b-2xl">
                <button type="submit" :disabled="submitting" class="btn-primary btn-lg w-full">
                    <svg x-show="submitting" x-cloak class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                    <svg x-show="!submitting" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
                    <span x-text="submitting ? 'AI sedang memilihkan...' : 'Buatkan Rekomendasi'">Buatkan Rekomendasi</span>
                </button>
                <p x-show="submitting" x-cloak class="mt-3 text-center text-xs text-ink/50" role="status" aria-live="polite">Mengirim permintaan ke AI…</p>
            </div>
        </form>
    </div>

    <x-slot name="head">
        <script>
            function aiRecommendationForm() {
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
                            this.locError = 'Izin lokasi ditolak. Rekomendasi tetap bisa dibuat tanpa lokasi.';
                        });
                    },
                };
            }
        </script>
    </x-slot>
</x-app-layout>
