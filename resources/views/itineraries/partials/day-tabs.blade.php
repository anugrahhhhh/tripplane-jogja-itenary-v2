@props(['itinerary'])
<div class="mt-6 -mx-4 px-4 sm:mx-0 sm:px-0 flex gap-2 overflow-x-auto scrollbar-none pb-1" role="tablist" aria-label="Pilih hari">
    @foreach ($itinerary->itemsByDay() as $day => $items)
        <button type="button" role="tab" @click="activeDay = {{ $day }}; renderDay();" :aria-selected="activeDay === {{ $day }}"
                :class="activeDay === {{ $day }} ? 'bg-primary text-white shadow-glow ring-primary' : 'bg-white text-ink/70 ring-primary-100 hover:ring-primary-300 hover:text-primary-700'"
                class="shrink-0 min-w-[5.5rem] rounded-2xl px-4 py-2.5 text-left ring-1 transition">
            <span class="flex items-center justify-between gap-2">
                <span class="block text-xs font-bold tracking-wide uppercase">Day {{ $day }}</span>
                <span class="text-[10px] font-semibold rounded-full px-1.5 py-px"
                      :class="activeDay === {{ $day }} ? 'bg-white/20' : 'bg-primary-50 text-primary-700'">{{ $items->count() }}</span>
            </span>
            <span class="block mt-0.5 text-xs opacity-80">{{ $itinerary->start_date->copy()->addDays($day - 1)->translatedFormat('D, d M') }}</span>
        </button>
    @endforeach
</div>
