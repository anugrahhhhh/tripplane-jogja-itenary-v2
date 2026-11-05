<x-app-layout>
    <x-slot name="header">
        <a href="{{ route('recommendations.create') }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-primary-700 hover:underline">
            &larr; Ubah preferensi
        </a>
        <div class="mt-3 flex items-center gap-3">
            <span class="w-12 h-12 rounded-2xl bg-gradient-to-br from-accent-400 to-accent-600 text-white flex items-center justify-center shadow-sm">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
            </span>
            <div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-ink">Rekomendasi Untukmu</h1>
                <p class="text-sm text-ink/60 mt-1">Dipilih oleh AI berdasarkan preferensi yang kamu berikan.</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if (empty($recommendations))
            <div class="empty-state">
                <span class="icon-tile w-14 h-14 mb-4">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                </span>
                <p class="font-semibold text-ink">AI tidak menemukan destinasi yang cocok. Coba ubah preferensimu.</p>
                <a href="{{ route('recommendations.create') }}" class="mt-5 btn-primary">Ubah preferensi</a>
            </div>
        @else
            @auth
                <form action="{{ route('today-itinerary.store') }}" method="POST" x-data="{ selected: [] }">
                    @csrf
                    <input type="hidden" name="lat" value="{{ $lat }}">
                    <input type="hidden" name="lng" value="{{ $lng }}">

                    <div class="sticky top-20 z-20 mb-6 card px-4 py-3 flex items-center justify-between gap-3 flex-wrap">
                        <p class="text-sm text-ink/60 flex items-center gap-2">
                            <span class="w-7 h-7 rounded-full bg-primary-50 text-primary-700 font-bold text-xs flex items-center justify-center" x-text="selected.length">0</span>
                            destinasi dipilih
                        </p>
                        <button type="submit" :disabled="selected.length === 0" class="btn-primary">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Tambahkan ke Itinerary Hari Ini
                        </button>
                    </div>

                    <div class="space-y-5">
                        @foreach ($recommendations as $item)
                            <label class="group block card overflow-hidden sm:flex cursor-pointer transition hover:shadow-lift has-[:checked]:ring-2 has-[:checked]:ring-primary-400 has-[:checked]:border-primary-300">
                                @include('recommendations.partials.result-card', ['item' => $item, 'rank' => $loop->iteration, 'selectable' => true])
                            </label>
                        @endforeach
                    </div>
                </form>
            @else
                <div class="alert-warning mb-6">
                    <svg class="w-5 h-5 shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /></svg>
                    <p><a href="{{ route('login') }}" class="font-semibold text-accent-600 hover:underline">Masuk</a> untuk bisa langsung menambahkan destinasi ini ke Itinerary Hari Ini.</p>
                </div>

                <div class="space-y-5">
                    @foreach ($recommendations as $item)
                        <div class="group card overflow-hidden sm:flex transition hover:shadow-lift">
                            @include('recommendations.partials.result-card', ['item' => $item, 'rank' => $loop->iteration, 'selectable' => false])
                        </div>
                    @endforeach
                </div>
            @endauth
        @endif
    </div>
</x-app-layout>
