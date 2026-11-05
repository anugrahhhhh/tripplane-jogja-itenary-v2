{{-- Item read-only untuk halaman publik: tanpa catatan dan tanpa kontrol. --}}
<li class="relative pl-12 pb-6">
    <span class="absolute left-0 top-3 w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center ring-4 ring-cream shadow-glow">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
    </span>

    @if (isset($travelInfoByDay[$day][$item->id]))
        <p class="flex items-center gap-1.5 text-xs text-ink/40 mb-2">
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
            &plusmn;{{ $travelInfoByDay[$day][$item->id]['distance_km'] }} km &middot; sekitar {{ $travelInfoByDay[$day][$item->id]['duration_minutes'] }} menit perjalanan
        </p>
    @endif

    <div class="bg-white border {{ ! empty($warningsByDay[$day][$item->id] ?? []) ? 'border-amber-300 ring-4 ring-amber-50' : 'border-primary-100/70' }} rounded-2xl p-4 shadow-soft">
        <p class="inline-flex items-center gap-1 rounded-lg bg-primary-50 px-2 py-0.5 text-xs font-bold text-primary-700">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            {{ \Illuminate\Support\Carbon::parse($item->start_time)->format('H:i') }}
        </p>
        <a href="{{ route('destinations.show', $item->destination) }}" class="mt-1.5 block font-bold text-ink hover:text-primary-700">{{ $item->destination->name }}</a>
        <p class="text-xs text-ink/50">Durasi {{ $item->duration_minutes }} menit</p>
        @foreach ($warningsByDay[$day][$item->id] ?? [] as $warning)
            <p class="mt-1 flex items-center gap-1 text-xs font-medium text-amber-600">
                <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                {{ $warning }}
            </p>
        @endforeach
    </div>
</li>
