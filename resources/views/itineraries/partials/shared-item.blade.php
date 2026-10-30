{{-- Item read-only untuk halaman publik: tanpa catatan dan tanpa kontrol. --}}
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
