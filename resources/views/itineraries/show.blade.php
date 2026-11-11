<x-app-layout>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8" x-data="itineraryMap()">
        <nav class="flex items-center gap-1.5 text-sm text-ink/50 mb-4" aria-label="Breadcrumb">
            <a href="{{ route('itineraries.index') }}" class="hover:text-primary-700">Itinerary</a>
            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" /></svg>
            <span class="font-semibold text-ink truncate">{{ $itinerary->title }}</span>
        </nav>

        <div class="relative overflow-hidden rounded-[2rem] bg-primary-900 text-white px-6 py-8 sm:px-10 sm:py-10 flex items-end justify-between flex-wrap gap-6">
            <div aria-hidden="true" class="pointer-events-none absolute inset-0 pattern-dots opacity-60"></div>
            <div aria-hidden="true" class="pointer-events-none absolute -top-24 -right-10 w-80 h-80 rounded-full bg-accent-500/25 blur-3xl"></div>
            <div class="relative flex items-center gap-5">
                <span class="hidden sm:flex w-16 h-16 shrink-0 rounded-2xl bg-white/10 ring-1 ring-white/15 items-center justify-center text-4xl">{{ $itinerary->emoji() }}</span>
                <div>
                    @if ($itinerary->type === 'today')
                        <span class="inline-flex items-center rounded-full bg-accent-200 text-primary-800 text-xs font-semibold px-2.5 py-0.5 mb-2">Itinerary Hari Ini</span>
                    @endif
                    <h1 class="text-2xl sm:text-4xl font-extrabold text-white">{{ $itinerary->title }}</h1>
                    <p class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-primary-100/80">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                            {{ $itinerary->start_date->translatedFormat('d F Y') }} &mdash; {{ $itinerary->end_date->translatedFormat('d F Y') }}
                        </span>
                        <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-semibold">{{ $itinerary->totalDays() }} Hari</span>
                        @if ($itinerary->traveler_count)
                            <span class="rounded-full bg-white/10 px-2.5 py-0.5 text-xs font-semibold">{{ $itinerary->traveler_count }} peserta</span>
                        @endif
                    </p>
                </div>
            </div>
            <div class="relative flex items-center gap-2 shrink-0">
                <a href="{{ route('itineraries.edit', $itinerary) }}" class="btn bg-white/10 text-white ring-1 ring-white/20 hover:bg-white/20">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z" /></svg>
                    Edit
                </a>
                <a href="{{ route('itineraries.pdf', $itinerary) }}" class="btn-accent">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                    Download PDF
                </a>
            </div>
        </div>

        <div class="mt-6">
            @include('itineraries.partials.cost-summary', ['cost' => $cost])
        </div>

        <!-- Day tabs -->
        @include('itineraries.partials.day-tabs', ['itinerary' => $itinerary])

        <div class="mt-6 grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="lg:col-span-2">
                @foreach ($itinerary->itemsByDay() as $day => $items)
                    <div x-show="activeDay === {{ $day }}" x-cloak>
                        <div class="card px-5 py-4 flex items-center justify-between flex-wrap gap-3">
                            <div>
                                <h2 class="text-lg font-bold text-ink">Day {{ $day }} &mdash; {{ $itinerary->start_date->copy()->addDays($day - 1)->translatedFormat('d F Y') }}</h2>
                                <p class="text-xs text-ink/50">{{ $items->count() }} destinasi</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($weatherByDay[$day] ?? null)
                                    <div class="inline-flex items-center gap-1.5 rounded-xl bg-gradient-to-r from-primary-50 to-accent-50 ring-1 ring-primary-100 px-3 py-2 text-xs text-primary-800" title="{{ $weatherByDay[$day]['label'] }}">
                                        <span class="text-base leading-none">{{ $weatherByDay[$day]['icon'] }}</span>
                                        <span class="font-semibold">{{ $weatherByDay[$day]['label'] }}</span>
                                        @if ($weatherByDay[$day]['temp_min'] !== null && $weatherByDay[$day]['temp_max'] !== null)
                                            <span class="text-primary-600">{{ round($weatherByDay[$day]['temp_min']) }}&ndash;{{ round($weatherByDay[$day]['temp_max']) }}&deg;C</span>
                                        @endif
                                        @if (($weatherByDay[$day]['precipitation_probability'] ?? null) !== null)
                                            <span class="text-primary-500">&middot; {{ $weatherByDay[$day]['precipitation_probability'] }}% hujan</span>
                                        @endif
                                    </div>
                                @endif
                                <form action="{{ route('itinerary-items.auto-sort', $itinerary) }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="day_number" value="{{ $day }}">
                                    <button type="submit" class="btn-secondary btn-sm py-2">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" /></svg>
                                        Urutkan Otomatis
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <ol class="mt-6 relative" data-itinerary="{{ $itinerary->id }}">
                            @if ($items->isNotEmpty())
                                <div class="absolute left-[15px] top-4 bottom-6 w-0.5 bg-gradient-to-b from-primary-200 via-primary-100 to-transparent"></div>
                            @endif

                            @forelse ($items as $item)
                                <li data-id="{{ $item->id }}" class="relative pl-12 pb-6" x-data="{ editing: false }">
                                    <span class="absolute left-0 top-3 w-8 h-8 rounded-full bg-primary text-white flex items-center justify-center ring-4 ring-cream shadow-glow">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                                    </span>

                                    @if (isset($travelInfoByDay[$day][$item->id]))
                                        <p class="flex items-center gap-1.5 text-xs text-ink/40 mb-2">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                            &plusmn;{{ $travelInfoByDay[$day][$item->id]['distance_km'] }} km &middot; sekitar {{ $travelInfoByDay[$day][$item->id]['duration_minutes'] }} menit perjalanan
                                        </p>
                                    @endif

                                    <div class="bg-white border {{ ! empty($warningsByDay[$day][$item->id] ?? []) ? 'border-amber-300 ring-4 ring-amber-50' : 'border-primary-100/70' }} rounded-2xl p-4 shadow-soft hover:shadow-lift transition">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="inline-flex items-center gap-1 rounded-lg bg-primary-50 px-2 py-0.5 text-xs font-bold text-primary-700">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                    {{ \Illuminate\Support\Carbon::parse($item->start_time)->format('H:i') }}
                                                </p>
                                                <a href="{{ route('destinations.show', $item->destination) }}" class="mt-1.5 block font-bold text-ink hover:text-primary-700">{{ $item->destination->name }}</a>
                                                <p class="text-xs text-ink/50">Durasi {{ $item->duration_minutes }} menit &mdash; {{ $item->destination->category->name }}</p>
                                                @if ($item->notes)
                                                    <p class="mt-1 text-sm text-ink/60">{{ $item->notes }}</p>
                                                @endif
                                                @foreach ($warningsByDay[$day][$item->id] ?? [] as $warning)
                                                    <p class="mt-1 flex items-center gap-1 text-xs font-medium text-amber-600">
                                                        <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                                        {{ $warning }}
                                                    </p>
                                                @endforeach
                                            </div>
                                            <div class="flex flex-col items-end gap-2 shrink-0">
                                                <div class="flex items-center gap-0.5">
                                                    <button type="button" onclick="moveItem(this, -1)" title="Naikkan urutan" class="text-ink/30 hover:text-primary p-1">
                                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 3a1 1 0 01.707.293l6 6a1 1 0 01-1.414 1.414L10 5.414l-5.293 5.293a1 1 0 01-1.414-1.414l6-6A1 1 0 0110 3z" clip-rule="evenodd" /></svg>
                                                    </button>
                                                    <button type="button" onclick="moveItem(this, 1)" title="Turunkan urutan" class="text-ink/30 hover:text-primary p-1">
                                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 17a1 1 0 01-.707-.293l-6-6a1 1 0 111.414-1.414L10 14.586l5.293-5.293a1 1 0 011.414 1.414l-6 6A1 1 0 0110 17z" clip-rule="evenodd" /></svg>
                                                    </button>
                                                </div>
                                                <div class="flex items-center gap-1.5">
                                                    <button type="button" @click="editing = ! editing" :aria-expanded="editing" class="text-xs font-semibold text-ink/60 border border-primary-100 rounded-full px-3 py-1 hover:bg-primary-50 hover:text-primary-700 transition" :class="editing && 'bg-primary-50 text-primary-700'">Atur waktu</button>
                                                    <form action="{{ route('itinerary-items.destroy', $item) }}" method="POST" data-confirm="Hapus destinasi ini dari itinerary?" data-confirm-title="Hapus data?" data-confirm-button="Ya, hapus">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-xs font-semibold text-red-500 border border-red-100 rounded-full px-3 py-1 hover:bg-red-50 transition">Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <form x-show="editing" x-cloak action="{{ route('itinerary-items.update', $item) }}" method="POST" class="mt-3 grid grid-cols-2 gap-2 pt-3 border-t border-primary-50">
                                            @csrf
                                            @method('PUT')
                                            <div>
                                                <label for="item-{{ $item->id }}-day" class="block text-[11px] font-semibold text-ink/60 mb-1">Hari</label>
                                                <select id="item-{{ $item->id }}-day" name="day_number" class="input text-xs py-2 w-full">
                                                    @foreach (range(1, $itinerary->totalDays()) as $d)
                                                        <option value="{{ $d }}" @selected($item->day_number === $d)>Hari ke-{{ $d }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label for="item-{{ $item->id }}-start" class="block text-[11px] font-semibold text-ink/60 mb-1">Jam mulai</label>
                                                <input id="item-{{ $item->id }}-start" type="time" name="start_time" value="{{ \Illuminate\Support\Carbon::parse($item->start_time)->format('H:i') }}" class="input text-xs py-2 w-full">
                                            </div>
                                            <div>
                                                <label for="item-{{ $item->id }}-duration" class="block text-[11px] font-semibold text-ink/60 mb-1">Durasi (menit)</label>
                                                <input id="item-{{ $item->id }}-duration" type="number" name="duration_minutes" value="{{ $item->duration_minutes }}" min="15" step="15" class="input text-xs py-2 w-full" placeholder="60">
                                            </div>
                                            <div>
                                                <label for="item-{{ $item->id }}-notes" class="block text-[11px] font-semibold text-ink/60 mb-1">Catatan</label>
                                                <input id="item-{{ $item->id }}-notes" type="text" name="notes" value="{{ $item->notes }}" placeholder="Opsional" class="input text-xs py-2 w-full">
                                            </div>
                                            <button type="submit" class="col-span-2 btn-primary btn-sm py-2">Simpan Perubahan</button>
                                        </form>
                                    </div>
                                </li>
                            @empty
                                <li class="text-sm text-ink/50 text-center py-4">Belum ada destinasi di hari ini.</li>
                            @endforelse
                        </ol>

                        <div x-data="{ showForm: false }" class="mt-2">
                            <div x-show="! showForm" class="border-2 border-dashed border-primary-200 bg-white/50 rounded-2xl p-6 text-center hover:border-primary-300 hover:bg-white transition">
                                <p class="text-sm text-ink/60">Masih ada tempat yang ingin kamu kunjungi?</p>
                                <button type="button" @click="showForm = true" class="mt-3 btn-secondary">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                    Tambah dari Kategori
                                </button>
                            </div>

                            <div x-show="showForm" x-cloak class="card p-5">
                                <div class="flex items-center justify-between mb-4">
                                    <p class="font-bold text-ink">Tambah destinasi ke Day {{ $day }}</p>
                                    <button type="button" @click="showForm = false" class="text-ink/40 hover:text-ink" aria-label="Tutup form">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                <form action="{{ route('itinerary-items.store', $itinerary) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @csrf
                                    <input type="hidden" name="day_number" value="{{ $day }}">
                                    <div class="sm:col-span-2">
                                    <label for="add-{{ $day }}-destination" class="block text-[11px] font-semibold text-ink/60 mb-1">Destinasi</label>
                                    <select id="add-{{ $day }}-destination" name="destination_id" required class="input w-full">
                                        <option value="">Pilih destinasi...</option>
                                        @if ($favoriteDestinations->isNotEmpty())
                                            <optgroup label="Dari Favorit">
                                                @foreach ($favoriteDestinations as $d)
                                                    <option value="{{ $d->id }}">{{ $d->name }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endif
                                        <optgroup label="Semua Destinasi">
                                            @foreach ($catalogDestinations as $d)
                                                <option value="{{ $d->id }}">{{ $d->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    </select>
                                    </div>
                                    <div>
                                        <label for="add-{{ $day }}-start" class="block text-[11px] font-semibold text-ink/60 mb-1">Jam mulai</label>
                                        <input id="add-{{ $day }}-start" type="time" name="start_time" value="09:00" required class="input w-full">
                                    </div>
                                    <div>
                                        <label for="add-{{ $day }}-duration" class="block text-[11px] font-semibold text-ink/60 mb-1">Durasi (menit)</label>
                                        <input id="add-{{ $day }}-duration" type="number" name="duration_minutes" value="60" min="15" step="15" class="input w-full" placeholder="60">
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label for="add-{{ $day }}-notes" class="block text-[11px] font-semibold text-ink/60 mb-1">Catatan <span class="font-normal text-ink/40">(opsional)</span></label>
                                        <input id="add-{{ $day }}-notes" type="text" name="notes" placeholder="Contoh: beli tiket di loket timur" class="input w-full">
                                    </div>
                                    <button type="submit" class="sm:col-span-2 btn-primary">
                                        Tambahkan ke Itinerary
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Sidebar -->
            <div class="lg:col-span-1">
                <div class="lg:sticky lg:top-24 space-y-5">
                    <div class="card p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-ink">Favorit</h3>
                            <span class="inline-flex items-center rounded-full bg-accent-100 text-accent-600 text-xs font-semibold px-2.5 py-0.5">
                                {{ $favoriteDestinations->count() }} tempat
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-ink/50">Destinasi yang kamu tandai favorit bisa langsung dimasukkan ke itinerary hari yang sedang dibuka.</p>

                        <div class="mt-4 space-y-3">
                            @forelse ($favoriteDestinations as $d)
                                <div class="flex items-center gap-3">
                                    <div class="w-11 h-11 rounded-lg bg-primary-50 shrink-0 overflow-hidden flex items-center justify-center">
                                        @if ($d->cover_photo)
                                            <img src="{{ asset('storage/'.$d->cover_photo) }}" class="w-full h-full object-cover" alt="{{ $d->name }}" loading="lazy" decoding="async">
                                        @else
                                            <x-category-icon :slug="$d->category->slug" class="w-4 h-4 text-primary-400" />
                                        @endif
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-ink truncate">{{ $d->name }}</p>
                                        <p class="text-xs text-ink/50 truncate">{{ $d->category->name }}</p>
                                    </div>
                                    <form action="{{ route('itinerary-items.store', $itinerary) }}" method="POST" class="shrink-0">
                                        @csrf
                                        <input type="hidden" name="destination_id" value="{{ $d->id }}">
                                        <input type="hidden" name="day_number" :value="activeDay">
                                        <input type="hidden" name="start_time" value="09:00">
                                        <input type="hidden" name="duration_minutes" value="60">
                                        <button type="submit" class="text-xs font-semibold text-accent-600 bg-accent-50 ring-1 ring-accent-200 rounded-lg px-2.5 py-1.5 hover:bg-accent-100 transition whitespace-nowrap">
                                            + Itinerary
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm text-ink/50">Belum ada favorit. <a href="{{ route('destinations.index') }}" class="text-accent-500 underline">Jelajahi katalog</a> untuk menyimpan favorit.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="card p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="font-bold text-ink">Kolaborator</h3>
                            <span class="inline-flex items-center rounded-full bg-primary-50 text-primary-700 text-xs font-semibold px-2.5 py-0.5">
                                {{ $itinerary->collaborators->count() }} orang
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-ink/50">
                            @if ($isOwner)
                                Undang teman untuk ikut lihat & susun itinerary ini bareng-bareng.
                            @else
                                Kamu ikut mengelola itinerary ini bersama {{ $itinerary->user->name }}.
                            @endif
                        </p>

                        <div class="mt-4 space-y-2">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-full bg-accent-100 text-accent-700 flex items-center justify-center text-xs font-semibold shrink-0">
                                    {{ strtoupper(substr($itinerary->user->name, 0, 1)) }}
                                </span>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-ink truncate">{{ $itinerary->user->name }}</p>
                                    <p class="text-xs text-ink/40">Pemilik</p>
                                </div>
                            </div>

                            @foreach ($itinerary->collaborators as $collaborator)
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-full bg-primary-100 text-primary-700 flex items-center justify-center text-xs font-semibold shrink-0">
                                        {{ strtoupper(substr($collaborator->name, 0, 1)) }}
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-ink truncate">{{ $collaborator->name }}</p>
                                        <p class="text-xs text-ink/40">Kolaborator</p>
                                    </div>
                                    @if ($isOwner || $collaborator->id === auth()->id())
                                        <form action="{{ route('itinerary-collaborators.destroy', [$itinerary, $collaborator]) }}" method="POST"
                                              data-confirm="{{ $collaborator->id === auth()->id() ? 'Keluar dari itinerary ini?' : 'Keluarkan '.$collaborator->name.' dari kolaborator?' }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-red-500 hover:underline shrink-0">
                                                {{ $collaborator->id === auth()->id() ? 'Keluar' : 'Keluarkan' }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        @if ($isOwner)
                            <form action="{{ route('itinerary-collaborators.store', $itinerary) }}" method="POST" class="mt-4 pt-4 border-t border-primary-50 flex gap-2">
                                @csrf
                                <label for="collaborator-email" class="sr-only">Email traveler yang ingin diundang sebagai kolaborator</label>
                                <input id="collaborator-email" type="email" name="email" required autocomplete="email" placeholder="Email traveler terdaftar" class="flex-1 min-w-0 input" @error('email') aria-invalid="true" aria-describedby="collaborator-email-error" @enderror>
                                <button type="submit" class="shrink-0 btn-primary btn-sm px-4">Undang</button>
                            </form>
                            @error('email')
                                <p id="collaborator-email-error" class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    @if ($isOwner)
                        <div class="card p-5" x-data="{ copied: false, failed: false }">
                            <h3 class="font-bold text-ink">Bagikan itinerary</h3>
                            <p class="mt-1 text-xs text-ink/50">Siapa pun yang punya link ini bisa melihat itinerary tanpa login. Catatan pribadi dan kolaborator tidak ditampilkan.</p>

                            @if ($itinerary->share_token)
                                <div class="mt-3 flex gap-2">
                                    <input type="text" readonly x-ref="shareUrl" aria-label="Link berbagi itinerary" value="{{ route('itineraries.shared', $itinerary->share_token) }}" class="flex-1 min-w-0 input text-xs py-2" onfocus="this.select()">
                                    <button type="button"
                                            @click="if (! navigator.clipboard) { failed = true; $refs.shareUrl.select(); return; } try { navigator.clipboard.writeText($refs.shareUrl.value).then(() => { copied = true; failed = false; setTimeout(() => copied = false, 2000); }).catch(() => { failed = true; $refs.shareUrl.select(); }); } catch (e) { failed = true; $refs.shareUrl.select(); }"
                                            class="shrink-0 btn-primary btn-sm px-4">
                                        <span x-show="! copied">Salin</span>
                                        <span x-show="copied" x-cloak>Tersalin</span>
                                    </button>
                                </div>
                                <p x-show="failed" x-cloak class="mt-1.5 text-xs text-amber-600">Salin manual: tekan Ctrl+C pada link yang terpilih.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <form action="{{ route('itineraries.share.store', $itinerary) }}" method="POST" data-confirm="Link lama tidak akan bisa dibuka lagi. Lanjutkan?">
                                        @csrf
                                        <button type="submit" class="btn-secondary btn-sm py-2">Buat link baru</button>
                                    </form>
                                    <form action="{{ route('itineraries.share.destroy', $itinerary) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger btn-sm py-2">Matikan link</button>
                                    </form>
                                </div>
                            @else
                                <form action="{{ route('itineraries.share.store', $itinerary) }}" method="POST" class="mt-3">
                                    @csrf
                                    <button type="submit" class="btn-primary btn-sm py-2">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244" /></svg>
                                        Buat link berbagi
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif

                    <div class="card p-4">
                        <h3 class="font-bold text-ink text-sm">Peta Rute &mdash; Day <span x-text="activeDay"></span></h3>
                        <div id="route-map" class="mt-3 h-64 rounded-xl ring-1 ring-primary-100 overflow-hidden z-0"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="head">
        @include('itineraries.partials.route-map', ['mapPointsByDay' => $mapPointsByDay])
        <script>
            function moveItem(button, direction) {
                const li = button.closest('li');
                const list = li.parentElement;
                const sibling = direction === -1 ? li.previousElementSibling : li.nextElementSibling;
                if (! sibling) {
                    return;
                }

                if (direction === -1) {
                    list.insertBefore(li, sibling);
                } else {
                    list.insertBefore(sibling, li);
                }

                const ids = Array.from(list.children)
                    .filter((el) => el.dataset.id)
                    .map((el) => el.dataset.id);
                const itineraryId = list.dataset.itinerary;

                fetch(`/itinerary/${itineraryId}/urutan`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ item_ids: ids }),
                });
            }
        </script>
    </x-slot>
</x-app-layout>
