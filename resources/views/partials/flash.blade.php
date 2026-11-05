{{-- Pesan status sesi (sukses) yang bisa ditutup. Kode status internal profil punya pesan sendiri di formnya. --}}
@if (session('status') && is_string(session('status')) && ! in_array(session('status'), ['profile-updated', 'password-updated', 'verification-link-sent'], true))
    <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4"
         x-data="{ show: true }" x-show="show"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0 -translate-y-1">
        <div class="alert-success shadow-soft" role="status">
            <svg class="w-5 h-5 shrink-0 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <p class="flex-1">{{ session('status') }}</p>
            <button type="button" @click="show = false" class="shrink-0 text-primary-600/60 hover:text-primary-800" aria-label="Tutup">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>
@endif

{{-- Error dari form yang tidak punya tempat error sendiri di halaman asalnya
     (mis. "Tambah ke Itinerary Hari Ini" dari halaman Terdekat/Rekomendasi). --}}
@php $flashError = isset($errors) ? ($errors->first('destination_ids') ?: $errors->first('destination_ids.*')) : null; @endphp
@if ($flashError)
    <div class="max-w-7xl mx-auto w-full px-4 sm:px-6 lg:px-8 mt-4"
         x-data="{ show: true }" x-show="show"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-end="opacity-0 -translate-y-1">
        <div class="alert-error shadow-soft" role="alert">
            <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
            <p class="flex-1">{{ $flashError }}</p>
            <button type="button" @click="show = false" class="shrink-0 text-red-600/60 hover:text-red-800" aria-label="Tutup">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    </div>
@endif
