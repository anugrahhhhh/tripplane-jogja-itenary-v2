<x-app-layout>
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8" x-data="itineraryMap()">
        <nav class="text-sm text-ink/50 mb-2">
            <a href="{{ route('itineraries.index') }}" class="hover:text-primary">Itinerary</a>
            <span class="mx-1">/</span>
            <span class="font-semibold text-ink">{{ $itinerary->title }}</span>
        </nav>

        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-ink">{{ $itinerary->title }}</h1>
                <p class="mt-1 text-sm text-ink/60">
                    {{ $itinerary->start_date->translatedFormat('d F Y') }} &mdash; {{ $itinerary->end_date->translatedFormat('d F Y') }}
                    &middot; {{ $itinerary->totalDays() }} Hari
                    @if ($itinerary->type === 'today')
                        <span class="ml-2 inline-flex items-center rounded-full bg-accent-200 text-primary-800 text-xs px-2 py-0.5">Itinerary Hari Ini</span>
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="{{ route('itineraries.edit', $itinerary) }}" class="inline-flex items-center gap-1.5 rounded-xl border border-primary-200 px-4 py-2.5 text-sm font-semibold text-ink/70 hover:bg-primary-50 transition">
                    Edit
                </a>
                <a href="{{ route('itineraries.pdf', $itinerary) }}" class="inline-flex items-center gap-1.5 rounded-xl bg-accent-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-accent-600 transition">
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
                        <div class="flex items-center justify-between flex-wrap gap-2">
                            <div>
                                <h2 class="font-semibold text-ink">Day {{ $day }} &mdash; {{ $itinerary->start_date->copy()->addDays($day - 1)->translatedFormat('d F Y') }}</h2>
                                <p class="text-xs text-ink/50">{{ $items->count() }} destinasi</p>
                            </div>
                            <div class="flex items-center gap-2">
                                @if ($weatherByDay[$day] ?? null)
                                    <div class="inline-flex items-center gap-1.5 rounded-lg bg-primary-50 px-3 py-2 text-xs text-primary-800" title="{{ $weatherByDay[$day]['label'] }}">
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
                                    <button type="submit" class="text-xs font-semibold text-ink/60 border border-primary-200 rounded-lg px-3 py-2 hover:bg-primary-50 transition">
                                        Urutkan Otomatis
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Timeline -->
                        <ol class="mt-5 relative" data-itinerary="{{ $itinerary->id }}">
                            @if ($items->isNotEmpty())
                                <div class="absolute left-[7px] top-2 bottom-2 w-px bg-primary-100"></div>
                            @endif

                            @forelse ($items as $item)
                                <li data-id="{{ $item->id }}" class="relative pl-8 pb-6" x-data="{ editing: false }">
                                    <span class="absolute left-0 top-1 w-[15px] h-[15px] rounded-full bg-primary border-2 border-white ring-2 ring-primary-100"></span>

                                    @if (isset($travelInfoByDay[$day][$item->id]))
                                        <p class="flex items-center gap-1.5 text-xs text-ink/40 mb-2">
                                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3" /></svg>
                                            &plusmn;{{ $travelInfoByDay[$day][$item->id]['distance_km'] }} km &middot; sekitar {{ $travelInfoByDay[$day][$item->id]['duration_minutes'] }} menit perjalanan
                                        </p>
                                    @endif

                                    <div class="bg-white border {{ ! empty($warningsByDay[$day][$item->id] ?? []) ? 'border-amber-300' : 'border-primary-100' }} rounded-xl p-4">
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <p class="text-sm font-bold text-primary-700">
                                                    {{ \Illuminate\Support\Carbon::parse($item->start_time)->format('H:i') }}
                                                </p>
                                                <p class="font-semibold text-ink">{{ $item->destination->name }}</p>
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
                                                    <button type="button" @click="editing = ! editing" class="text-xs font-medium text-ink/60 border border-primary-100 rounded-full px-3 py-1 hover:bg-primary-50 transition">Atur waktu</button>
                                                    <form action="{{ route('itinerary-items.destroy', $item) }}" method="POST" onsubmit="return confirm('Hapus destinasi ini dari itinerary?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-xs font-medium text-red-500 border border-red-100 rounded-full px-3 py-1 hover:bg-red-50 transition">Hapus</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <form x-show="editing" x-cloak action="{{ route('itinerary-items.update', $item) }}" method="POST" class="mt-3 grid grid-cols-2 gap-2 pt-3 border-t border-primary-50">
                                            @csrf
                                            @method('PUT')
                                            <select name="day_number" class="rounded-lg border-primary-100 text-xs focus:border-primary focus:ring-primary">
                                                @foreach (range(1, $itinerary->totalDays()) as $d)
                                                    <option value="{{ $d }}" @selected($item->day_number === $d)>Hari ke-{{ $d }}</option>
                                                @endforeach
                                            </select>
                                            <input type="time" name="start_time" value="{{ \Illuminate\Support\Carbon::parse($item->start_time)->format('H:i') }}" class="rounded-lg border-primary-100 text-xs focus:border-primary focus:ring-primary">
                                            <input type="number" name="duration_minutes" value="{{ $item->duration_minutes }}" min="15" step="15" class="rounded-lg border-primary-100 text-xs focus:border-primary focus:ring-primary" placeholder="Durasi (menit)">
                                            <input type="text" name="notes" value="{{ $item->notes }}" placeholder="Catatan" class="rounded-lg border-primary-100 text-xs focus:border-primary focus:ring-primary">
                                            <button type="submit" class="col-span-2 rounded-lg bg-primary-700 text-white text-xs font-semibold py-2 hover:bg-primary-800 transition">Simpan Perubahan</button>
                                        </form>
                                    </div>
                                </li>
                            @empty
                                <li class="pl-8 text-sm text-ink/50">Belum ada destinasi di hari ini.</li>
                            @endforelse
                        </ol>

                        <div x-data="{ showForm: false }" class="mt-2">
                            <div x-show="! showForm" class="border-2 border-dashed border-primary-100 rounded-xl p-6 text-center">
                                <p class="text-sm text-ink/60">Masih ada tempat yang ingin kamu kunjungi?</p>
                                <button type="button" @click="showForm = true" class="mt-3 inline-flex items-center gap-1.5 rounded-lg border border-primary-200 px-4 py-2 text-sm font-semibold text-ink/70 hover:bg-primary-50 transition">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                                    Tambah dari Kategori
                                </button>
                            </div>

                            <div x-show="showForm" x-cloak class="bg-white border border-primary-100 rounded-xl p-5">
                                <form action="{{ route('itinerary-items.store', $itinerary) }}" method="POST" class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    @csrf
                                    <input type="hidden" name="day_number" value="{{ $day }}">
                                    <select name="destination_id" required class="sm:col-span-2 rounded-lg border-primary-100 text-sm focus:border-primary focus:ring-primary">
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
                                    <input type="time" name="start_time" value="09:00" required class="rounded-lg border-primary-100 text-sm focus:border-primary focus:ring-primary">
                                    <input type="number" name="duration_minutes" value="60" min="15" step="15" class="rounded-lg border-primary-100 text-sm focus:border-primary focus:ring-primary" placeholder="Durasi (menit)">
                                    <input type="text" name="notes" placeholder="Catatan (opsional)" class="sm:col-span-2 rounded-lg border-primary-100 text-sm focus:border-primary focus:ring-primary">
                                    <button type="submit" class="sm:col-span-2 rounded-lg bg-primary text-white text-sm font-semibold py-2.5 hover:bg-primary-600 transition">
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
                <div class="sticky top-24 space-y-5">
                    <div class="bg-white border border-primary-100 rounded-2xl p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-ink">Favorit</h3>
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
                                            <img src="{{ asset('storage/'.$d->cover_photo) }}" class="w-full h-full object-cover" alt="{{ $d->name }}">
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
                                        <button type="submit" class="text-xs font-semibold text-accent-500 border border-accent-200 rounded-lg px-2.5 py-1.5 hover:bg-accent-50 transition whitespace-nowrap">
                                            + Itinerary
                                        </button>
                                    </form>
                                </div>
                            @empty
                                <p class="text-sm text-ink/50">Belum ada favorit. <a href="{{ route('destinations.index') }}" class="text-accent-500 underline">Jelajahi katalog</a> untuk menyimpan favorit.</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-white border border-primary-100 rounded-2xl p-5">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-ink">Kolaborator</h3>
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
                                              onsubmit="return confirm('{{ $collaborator->id === auth()->id() ? 'Keluar dari itinerary ini?' : 'Keluarkan '.$collaborator->name.' dari kolaborator?' }}')">
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
                                <input type="email" name="email" required placeholder="Email traveler terdaftar" class="flex-1 min-w-0 rounded-lg border-primary-100 text-sm focus:border-primary focus:ring-primary">
                                <button type="submit" class="shrink-0 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white hover:bg-primary-600 transition">Undang</button>
                            </form>
                            @error('email')
                                <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    @if ($isOwner)
                        <div class="bg-white border border-primary-100 rounded-2xl p-5" x-data="{ copied: false, failed: false }">
                            <h3 class="font-semibold text-ink">Bagikan itinerary</h3>
                            <p class="mt-1 text-xs text-ink/50">Siapa pun yang punya link ini bisa melihat itinerary tanpa login. Catatan pribadi dan kolaborator tidak ditampilkan.</p>

                            @if ($itinerary->share_token)
                                <div class="mt-3 flex gap-2">
                                    <input type="text" readonly x-ref="shareUrl" value="{{ route('itineraries.shared', $itinerary->share_token) }}" class="flex-1 min-w-0 rounded-lg border-primary-100 text-xs focus:border-primary focus:ring-primary" onfocus="this.select()">
                                    <button type="button"
                                            @click="if (! navigator.clipboard) { failed = true; $refs.shareUrl.select(); return; } try { navigator.clipboard.writeText($refs.shareUrl.value).then(() => { copied = true; failed = false; setTimeout(() => copied = false, 2000); }).catch(() => { failed = true; $refs.shareUrl.select(); }); } catch (e) { failed = true; $refs.shareUrl.select(); }"
                                            class="shrink-0 rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white hover:bg-primary-600 transition">
                                        <span x-show="! copied">Salin</span>
                                        <span x-show="copied" x-cloak>Tersalin</span>
                                    </button>
                                </div>
                                <p x-show="failed" x-cloak class="mt-1.5 text-xs text-amber-600">Salin manual: tekan Ctrl+C pada link yang terpilih.</p>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <form action="{{ route('itineraries.share.store', $itinerary) }}" method="POST" onsubmit="return confirm('Link lama tidak akan bisa dibuka lagi. Lanjutkan?')">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-ink/60 border border-primary-200 rounded-lg px-3 py-2 hover:bg-primary-50 transition">Buat link baru</button>
                                    </form>
                                    <form action="{{ route('itineraries.share.destroy', $itinerary) }}" method="POST">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-500 border border-red-100 rounded-lg px-3 py-2 hover:bg-red-50 transition">Matikan link</button>
                                    </form>
                                </div>
                            @else
                                <form action="{{ route('itineraries.share.store', $itinerary) }}" method="POST" class="mt-3">
                                    @csrf
                                    <button type="submit" class="rounded-lg bg-primary px-3 py-2 text-xs font-semibold text-white hover:bg-primary-600 transition">Buat link berbagi</button>
                                </form>
                            @endif
                        </div>
                    @endif

                    <div class="bg-white border border-primary-100 rounded-2xl p-4">
                        <h3 class="font-semibold text-ink text-sm">Peta Rute &mdash; Day <span x-text="activeDay"></span></h3>
                        <div id="route-map" class="mt-3 h-64 rounded-xl z-0"></div>
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
