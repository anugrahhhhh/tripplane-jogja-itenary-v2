<x-app-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between flex-wrap gap-5">
            <div>
                <p class="eyebrow">Itinerary</p>
                <h1 class="mt-2 text-3xl sm:text-4xl font-extrabold text-ink">Perjalananmu</h1>
                <p class="mt-2 text-sm text-ink/60">Kelola semua itinerary yang pernah kamu buat di sini.</p>
            </div>
            <div class="flex flex-wrap gap-2 shrink-0">
                <a href="{{ route('itineraries.ai-create') }}" class="btn-accent">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" /></svg>
                    Susun dengan AI
                </a>
                <a href="{{ route('itineraries.create') }}" class="btn-primary">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                    Buat Manual
                </a>
            </div>
        </div>
    </x-slot>

    @php
        $filterCards = collect($itineraries->items())->map(fn ($it) => [
            't' => \Illuminate\Support\Str::lower($it->title),
            's' => $it->isActive() ? 'aktif' : 'selesai',
        ])->values();
    @endphp
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10"
         x-data="{
            q: '',
            status: 'semua',
            cards: {{ \Illuminate\Support\Js::from($filterCards) }},
            matches(i) {
                const c = this.cards[i];
                return !! c && (this.status === 'semua' || this.status === c.s) && c.t.includes(this.q.toLowerCase().trim());
            },
            get visible() { return this.cards.filter((c, i) => this.matches(i)).length; },
         }">
        @if ($itineraries->isEmpty())
            <div class="empty-state">
                <span class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary-100 to-accent-100 flex items-center justify-center text-3xl mb-4">🗺️</span>
                <h2 class="text-lg font-bold text-ink">Belum ada itinerary</h2>
                <p class="mt-1 text-ink/60 max-w-sm">Kamu belum punya itinerary. Yuk mulai susun rencana perjalananmu!</p>
                <div class="mt-6 flex flex-wrap justify-center gap-2">
                    <a href="{{ route('itineraries.create') }}" class="btn-primary">Buat Itinerary Pertama</a>
                    <a href="{{ route('itineraries.ai-create') }}" class="btn-secondary">Coba susun dengan AI</a>
                </div>
            </div>
        @else
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="relative flex-1">
                    <svg class="absolute left-4 top-1/2 -translate-y-1/2 w-4 h-4 text-ink/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    <label for="itinerary-search" class="sr-only">Cari nama perjalanan</label>
                    <input id="itinerary-search" type="search" x-model="q" placeholder="Cari nama perjalanan..." class="input pl-11 py-3">
                </div>
                <div class="inline-flex rounded-xl bg-white p-1 ring-1 ring-primary-100 shadow-sm" role="tablist" aria-label="Filter status">
                    @foreach (['semua' => 'Semua', 'aktif' => 'Aktif', 'selesai' => 'Selesai'] as $value => $label)
                        <button type="button" role="tab" @click="status = '{{ $value }}'" :aria-selected="status === '{{ $value }}'"
                                :class="status === '{{ $value }}' ? 'bg-primary text-white shadow-sm' : 'text-ink/60 hover:text-ink'"
                                class="flex-1 sm:flex-none rounded-lg px-4 py-2 text-sm font-semibold transition">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="mt-6 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($itineraries as $itinerary)
                    @php
                        $progress = $itinerary->plannedProgress();
                        $active = $itinerary->isActive();
                    @endphp
                    <div data-itinerary-card
                         x-show="matches({{ $loop->index }})"
                         class="group card card-hover flex flex-col">
                        <div class="relative h-32 rounded-t-2xl flex items-center justify-center overflow-hidden {{ $loop->index % 2 === 0 ? 'bg-gradient-to-br from-primary-50 to-primary-100' : 'bg-gradient-to-br from-accent-50 to-accent-100' }}">
                            <div aria-hidden="true" class="absolute inset-0 pattern-dots-dark"></div>
                            <span class="absolute top-3 left-3 inline-flex items-center gap-1.5 rounded-full bg-white/90 backdrop-blur px-2.5 py-1 text-[10px] font-bold tracking-wide uppercase {{ $active ? 'text-primary-700' : 'text-ink/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $active ? 'bg-primary-500' : 'bg-ink/30' }}"></span>
                                {{ $active ? 'Aktif' : 'Selesai' }}
                            </span>
                            <span class="absolute top-3 right-3 inline-flex items-center rounded-full bg-white/90 backdrop-blur px-2.5 py-1 text-[10px] font-bold text-ink/60">
                                {{ $itinerary->totalDays() }} Hari
                            </span>
                            <span class="relative text-5xl transition duration-300 group-hover:scale-110">{{ $itinerary->emoji() }}</span>
                        </div>
                        <div class="p-5 flex-1 flex flex-col">
                            @if ($itinerary->user_id !== auth()->id())
                                <span class="self-start badge-accent mb-2">
                                    Kolaborasi &middot; {{ $itinerary->user->name }}
                                </span>
                            @endif
                            <h3 class="font-bold text-lg text-ink line-clamp-1">{{ $itinerary->title }}</h3>
                            <p class="mt-1 flex items-center gap-1.5 text-xs text-ink/50">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                                {{ $itinerary->start_date->translatedFormat('d M Y') }} &mdash; {{ $itinerary->end_date->translatedFormat('d M Y') }}
                                &middot; {{ $itinerary->totalDays() }} Hari
                            </p>

                            <div class="mt-4">
                                <div class="flex items-center justify-between text-xs mb-1.5">
                                    <span class="text-ink/50">Progres rencana</span>
                                    <span class="font-bold {{ $progress >= 100 ? 'text-primary-600' : 'text-ink/70' }}">{{ $progress }}%</span>
                                </div>
                                <div class="h-2 bg-primary-50 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full bg-gradient-to-r from-accent-400 to-primary-500" style="width: {{ $progress }}%"></div>
                                </div>
                                <p class="mt-1.5 text-xs text-ink/50">{{ $progress }}% itinerary sudah direncanakan</p>
                            </div>

                            <div class="mt-auto pt-5 flex gap-2">
                                <a href="{{ route('itineraries.show', $itinerary) }}" class="btn-primary flex-1">
                                    Buka Itinerary
                                </a>
                                <div x-data="{ open: false }" class="relative">
                                    <button type="button" @click="open = ! open" @keydown.escape.window="open = false" :aria-expanded="open" aria-haspopup="true" aria-label="Opsi lainnya" class="w-10 h-10 rounded-xl border border-primary-100 bg-white flex items-center justify-center text-ink/50 hover:bg-primary-50 hover:text-primary-700 transition" :class="open && 'bg-primary-50 text-primary-700'">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4zm0 6a2 2 0 110-4 2 2 0 010 4z" /></svg>
                                    </button>
                                    <div x-show="open" x-cloak @click.outside="open = false"
                                         x-transition:enter="transition ease-out duration-100" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                                         class="absolute right-0 top-full mt-2 w-48 origin-top-right rounded-2xl border border-primary-100 bg-white shadow-lift p-1 z-30">
                                        @if ($itinerary->user_id === auth()->id())
                                            <a href="{{ route('itineraries.edit', $itinerary) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-ink/70 hover:bg-primary-50 hover:text-primary-700">
                                                <svg class="w-4 h-4 text-ink/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                                                Edit
                                            </a>
                                        @endif
                                        <a href="{{ route('itineraries.pdf', $itinerary) }}" class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm text-ink/70 hover:bg-primary-50 hover:text-primary-700">
                                            <svg class="w-4 h-4 text-ink/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                            Download PDF
                                        </a>
                                        <div class="my-1 border-t border-primary-50"></div>
                                        @if ($itinerary->user_id === auth()->id())
                                            <form action="{{ route('itineraries.destroy', $itinerary) }}" method="POST" data-confirm="Hapus itinerary ini beserta seluruh jadwalnya?" data-confirm-title="Hapus data?" data-confirm-button="Ya, hapus">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-full flex items-center gap-2 rounded-lg text-left px-3 py-2 text-sm text-red-500 hover:bg-red-50">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                                    Hapus
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('itinerary-collaborators.destroy', [$itinerary, auth()->user()]) }}" method="POST" data-confirm="Keluar dari itinerary ini?">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="w-full flex items-center gap-2 rounded-lg text-left px-3 py-2 text-sm text-red-500 hover:bg-red-50">
                                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                                                    Keluar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div x-show="visible === 0" x-cloak class="empty-state mt-6">
                <p class="font-semibold text-ink">Tidak ada itinerary yang cocok.</p>
                <p class="mt-1 text-sm text-ink/50">Coba kata kunci lain atau ubah filter status.</p>
            </div>

            <div class="mt-10">
                {{ $itineraries->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
