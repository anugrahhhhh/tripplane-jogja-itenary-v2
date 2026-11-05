{{-- Fitur yang ditampilkan di panel kiri halaman masuk/daftar. --}}
<ul class="mt-10 space-y-3">
    @foreach ([
        ['Favorit', $favorite ?? 'Simpan tempat yang ingin kamu kunjungi', 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z'],
        ['Itinerary', $itinerary ?? 'Susun perjalanan berdasarkan hari', 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5'],
        ['Explore', $explore ?? 'Temukan destinasi baru', 'M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z'],
    ] as [$title, $text, $icon])
        <li class="flex items-center gap-3 rounded-2xl bg-white/5 ring-1 ring-white/10 p-3 backdrop-blur-sm">
            <span class="w-10 h-10 shrink-0 rounded-xl bg-white/10 text-accent-200 flex items-center justify-center">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}" /></svg>
            </span>
            <span>
                <span class="block text-sm font-semibold text-white">{{ $title }}</span>
                <span class="block text-xs text-primary-200/80">{{ $text }}</span>
            </span>
        </li>
    @endforeach
</ul>
