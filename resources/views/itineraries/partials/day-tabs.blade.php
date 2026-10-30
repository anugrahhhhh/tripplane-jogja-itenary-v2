@props(['itinerary'])
<div class="mt-6 flex gap-2 overflow-x-auto pb-1">
    @foreach ($itinerary->itemsByDay() as $day => $items)
        <button type="button" @click="activeDay = {{ $day }}; renderDay();"
                :class="activeDay === {{ $day }} ? 'bg-primary text-white' : 'bg-white border border-primary-100 text-ink/70 hover:bg-primary-50'"
                class="shrink-0 rounded-xl px-4 py-2.5 text-left transition">
            <span class="block text-xs font-bold tracking-wide uppercase">Day {{ $day }}</span>
            <span class="block text-xs opacity-80">{{ $itinerary->start_date->copy()->addDays($day - 1)->translatedFormat('d M') }}</span>
        </button>
    @endforeach
</div>
