<x-app-layout>
    <x-slot name="header">
        <nav class="flex items-center gap-1.5 text-sm text-ink/50" aria-label="Breadcrumb">
            <a href="{{ route('itineraries.index') }}" class="hover:text-primary-700">Itinerary</a>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-ink">Buat baru</span>
        </nav>
        <h1 class="mt-3 text-3xl font-extrabold text-ink">Buat Itinerary Baru</h1>
        <p class="mt-1 text-sm text-ink/60">Beri nama perjalananmu dan tentukan tanggalnya. Destinasi bisa ditambahkan setelahnya.</p>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid grid-cols-1 lg:grid-cols-3 gap-6">
        <form action="{{ route('itineraries.store') }}" method="POST" data-warn-unsaved class="lg:col-span-2 card p-6 sm:p-8 space-y-5">
            @csrf

            <div>
                <x-input-label for="title" value="Nama Itinerary" />
                <x-text-input id="title" name="title" type="text" class="mt-1.5 block w-full" value="{{ old('title') }}" placeholder="Contoh: Liburan Jogja 3 Hari" required autofocus />
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

            <div>
                <x-input-label for="traveler_count" value="Jumlah peserta" />
                <x-text-input id="traveler_count" name="traveler_count" type="number" min="1" max="50" class="mt-1.5 block w-full sm:w-40" value="{{ old('traveler_count', 1) }}" required />
                <p class="mt-1.5 text-xs text-ink/50">Dipakai untuk menghitung estimasi biaya rombongan.</p>
                <x-input-error :messages="$errors->get('traveler_count')" class="mt-1.5" />
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-4 border-t border-primary-50">
                <a href="{{ route('itineraries.index') }}" class="btn-secondary">Batal</a>
                <x-primary-button>Buat Itinerary</x-primary-button>
            </div>
        </form>

        <aside class="space-y-4">
            <div class="rounded-2xl bg-gradient-to-br from-accent-50 to-primary-50 ring-1 ring-accent-200 p-5">
                <span class="badge-accent">Lebih cepat</span>
                <p class="mt-3 font-bold text-ink">Biarkan AI yang menyusun</p>
                <p class="mt-1 text-sm text-ink/60">Pilih minat dan budget, lalu dapatkan jadwal lengkap per hari secara otomatis.</p>
                <a href="{{ route('itineraries.ai-create') }}" class="mt-4 btn-accent btn-sm py-2">Susun dengan AI &rarr;</a>
            </div>
            <div class="card p-5">
                <p class="font-bold text-ink text-sm">Tips</p>
                <ul class="mt-3 space-y-2.5 text-sm text-ink/60">
                    <li class="flex gap-2"><span class="text-primary-500">&#10003;</span> Simpan destinasi ke favorit dulu agar mudah ditambahkan.</li>
                    <li class="flex gap-2"><span class="text-primary-500">&#10003;</span> Gunakan "Urutkan Otomatis" untuk rute paling efisien.</li>
                    <li class="flex gap-2"><span class="text-primary-500">&#10003;</span> Undang teman untuk menyusun bersama.</li>
                </ul>
            </div>
        </aside>
    </div>
</x-app-layout>
