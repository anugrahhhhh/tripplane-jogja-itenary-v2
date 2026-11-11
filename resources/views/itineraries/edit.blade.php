<x-app-layout>
    <x-slot name="header">
        <nav class="flex items-center gap-1.5 text-sm text-ink/50" aria-label="Breadcrumb">
            <a href="{{ route('itineraries.index') }}" class="hover:text-primary-700">Itinerary</a>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            <a href="{{ route('itineraries.show', $itinerary) }}" class="hover:text-primary-700 truncate max-w-[12rem]">{{ $itinerary->title }}</a>
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-ink">Edit</span>
        </nav>
        <h1 class="mt-3 text-3xl font-extrabold text-ink">Edit Itinerary</h1>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <form action="{{ route('itineraries.update', $itinerary) }}" method="POST" data-warn-unsaved class="card p-6 sm:p-8 space-y-5">
            @csrf
            @method('PUT')

            <div>
                <x-input-label for="title" value="Nama Itinerary" />
                <x-text-input id="title" name="title" type="text" class="mt-1.5 block w-full" value="{{ old('title', $itinerary->title) }}" required autofocus />
                <x-input-error :messages="$errors->get('title')" class="mt-1.5" />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <x-input-label for="start_date" value="Tanggal Mulai" />
                    <x-text-input id="start_date" name="start_date" type="date" class="mt-1.5 block w-full" value="{{ old('start_date', $itinerary->start_date->format('Y-m-d')) }}" required />
                    <x-input-error :messages="$errors->get('start_date')" class="mt-1.5" />
                </div>
                <div>
                    <x-input-label for="end_date" value="Tanggal Selesai" />
                    <x-text-input id="end_date" name="end_date" type="date" class="mt-1.5 block w-full" value="{{ old('end_date', $itinerary->end_date->format('Y-m-d')) }}" required />
                    <x-input-error :messages="$errors->get('end_date')" class="mt-1.5" />
                </div>
            </div>

            <div>
                <x-input-label for="traveler_count" value="Jumlah peserta" />
                <x-text-input id="traveler_count" name="traveler_count" type="number" min="1" max="50" class="mt-1.5 block w-full sm:w-40" value="{{ old('traveler_count', $itinerary->traveler_count ?? 1) }}" required />
                <x-input-error :messages="$errors->get('traveler_count')" class="mt-1.5" />
            </div>

            <div class="alert-warning">
                <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                <p class="text-xs">Catatan: jika rentang hari dipersempit, destinasi yang berada di hari yang terhapus perlu dipindah manual dari halaman itinerary.</p>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-4 border-t border-primary-50">
                <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-secondary">Batal</a>
                <x-primary-button>Simpan Perubahan</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
