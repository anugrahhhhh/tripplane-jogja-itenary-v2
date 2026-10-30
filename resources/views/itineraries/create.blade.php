<x-app-layout>
    <x-slot name="header">
        <h1 class="text-2xl font-bold text-ink">Buat Itinerary Baru</h1>
    </x-slot>

    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <form action="{{ route('itineraries.store') }}" method="POST" class="bg-white rounded-2xl border border-primary-100 p-6 space-y-4">
            @csrf

            <div>
                <x-input-label for="title" value="Nama Itinerary" />
                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" value="{{ old('title') }}" placeholder="Contoh: Liburan Jogja 3 Hari" required autofocus />
                <x-input-error :messages="$errors->get('title')" class="mt-1" />
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="start_date" value="Tanggal Mulai" />
                    <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" value="{{ old('start_date') }}" required />
                    <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="end_date" value="Tanggal Selesai" />
                    <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" value="{{ old('end_date') }}" required />
                    <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
                </div>
            </div>

            <div>
                <x-input-label for="traveler_count" value="Jumlah peserta" />
                <x-text-input id="traveler_count" name="traveler_count" type="number" min="1" max="50" class="mt-1 block w-full" value="{{ old('traveler_count', 1) }}" required />
                <x-input-error :messages="$errors->get('traveler_count')" class="mt-1" />
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <a href="{{ route('itineraries.index') }}" class="inline-flex items-center rounded-lg border border-primary-200 px-4 py-2.5 text-sm font-semibold text-primary-700 hover:bg-primary-50 transition">Batal</a>
                <x-primary-button>Buat Itinerary</x-primary-button>
            </div>
        </form>
    </div>
</x-app-layout>
