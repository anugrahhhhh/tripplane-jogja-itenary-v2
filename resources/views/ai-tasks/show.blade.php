@php
    $isItinerary = $task->type === \App\Models\AiTask::TYPE_ITINERARY;
    $failed = $task->status === \App\Models\AiTask::STATUS_FAILED;
@endphp
<x-app-layout>
    <x-slot name="title">{{ $isItinerary ? 'Menyusun Itinerary' : 'Mencari Rekomendasi' }}</x-slot>

    <x-slot name="head">
        {{-- Tanpa JavaScript: muat ulang berkala, server mengarahkan ke hasil bila selesai. --}}
        @unless ($failed)
            <noscript><meta http-equiv="refresh" content="4"></noscript>
        @endunless
        <script>
            function aiTaskProgress(config) {
                return {
                    status: config.status,
                    error: config.error,
                    elapsed: config.elapsed,
                    offline: false,
                    timer: null,
                    ticker: null,
                    steps: config.steps,
                    get step() {
                        if (this.elapsed < 4) return 0;
                        if (this.elapsed < 15) return 1;
                        return 2;
                    },
                    get waitHint() {
                        if (this.elapsed < 20) return 'Biasanya selesai dalam 5–20 detik.';
                        if (this.elapsed < 45) return 'AI sedang ramai, sebentar lagi ya…';
                        return 'Masih diproses. Kami otomatis mencoba model AI cadangan bila perlu.';
                    },
                    init() {
                        if (this.status === 'failed') return;
                        this.ticker = setInterval(() => this.elapsed++, 1000);
                        this.poll(1200);
                    },
                    destroy() {
                        clearTimeout(this.timer);
                        clearInterval(this.ticker);
                    },
                    poll(delay) {
                        this.timer = setTimeout(async () => {
                            try {
                                const res = await fetch(config.statusUrl, { headers: { 'Accept': 'application/json' }, cache: 'no-store' });
                                if (res.status === 429) return this.poll(5000);
                                if (! res.ok) throw new Error(res.status);
                                const data = await res.json();
                                this.offline = false;
                                this.status = data.status;
                                this.elapsed = Math.max(this.elapsed, data.elapsed);
                                if (data.status === 'completed' && data.redirect) {
                                    clearInterval(this.ticker);
                                    window.location.assign(data.redirect);
                                    return;
                                }
                                if (data.status === 'failed') {
                                    clearInterval(this.ticker);
                                    this.error = data.error;
                                    this.$nextTick(() => this.$refs.failure?.focus());
                                    return;
                                }
                                this.poll(this.elapsed > 20 ? 3000 : 1500);
                            } catch (e) {
                                this.offline = true;
                                this.poll(4000);
                            }
                        }, delay);
                    },
                };
            }
        </script>
    </x-slot>

    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16"
         x-data="aiTaskProgress(@js([
             'status' => $task->status,
             'error' => $task->error,
             'elapsed' => (int) $task->created_at->diffInSeconds(now()),
             'statusUrl' => route('ai-tasks.status', $task),
             'steps' => $isItinerary
                ? ['Menyiapkan katalog destinasi', 'AI menyusun rute & jadwal per hari', 'Menyimpan itinerary kamu']
                : ['Menyiapkan katalog destinasi', 'AI mencocokkan dengan preferensimu', 'Menyiapkan hasil rekomendasi'],
         ]))">

        @if (session('error'))
            <div class="alert-error mb-6" role="alert">
                <p>{{ session('error') }}</p>
            </div>
        @endif

        {{-- Sedang diproses --}}
        <section class="card p-8 sm:p-10 text-center" x-show="status !== 'failed'" @if ($failed) x-cloak @endif aria-labelledby="judul-proses">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-gradient-to-br from-accent-400 to-accent-600 text-white flex items-center justify-center shadow-sm">
                <svg class="w-8 h-8 motion-safe:animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09z" /></svg>
            </div>
            <h1 id="judul-proses" class="mt-5 text-2xl font-extrabold text-ink">
                {{ $isItinerary ? 'AI sedang menyusun itinerary-mu' : 'AI sedang mencari rekomendasi untukmu' }}
            </h1>
            <p class="mt-2 text-sm text-ink/60" role="status" aria-live="polite" x-text="waitHint">Biasanya selesai dalam 5–20 detik.</p>

            <ol class="mt-8 space-y-3 text-left max-w-sm mx-auto">
                <template x-for="(label, index) in steps" :key="index">
                    <li class="flex items-center gap-3 text-sm" :aria-current="index === step ? 'step' : null">
                        <span class="w-6 h-6 shrink-0 rounded-full flex items-center justify-center text-xs font-bold"
                              :class="index < step ? 'bg-primary text-white' : (index === step ? 'bg-accent-100 text-accent-700 ring-2 ring-accent-300' : 'bg-cream text-ink/40')">
                            <span x-text="index < step ? '✓' : index + 1"></span>
                        </span>
                        <span :class="index <= step ? 'text-ink font-medium' : 'text-ink/40'" x-text="label"></span>
                    </li>
                </template>
            </ol>

            <div class="mt-8 h-1.5 w-full rounded-full bg-primary-50 overflow-hidden" aria-hidden="true">
                <div class="h-full rounded-full bg-primary transition-all duration-1000 ease-out"
                     :style="`width: ${Math.min(95, 8 + elapsed * 3)}%`" style="width: 8%"></div>
            </div>
            <p class="mt-3 text-xs text-ink/40"><span x-text="elapsed">0</span> detik</p>

            <p x-show="offline" x-cloak class="mt-4 text-sm text-amber-700" role="alert">Koneksi terputus. Mencoba menghubungkan lagi…</p>

            <p class="mt-6 text-xs text-ink/50">
                @if ($isItinerary)
                    Kamu boleh meninggalkan halaman ini. Itinerary yang selesai disusun tetap tersimpan di menu
                    <a href="{{ route('itineraries.index') }}" class="font-semibold text-primary-700 hover:underline">Itinerary</a>.
                @else
                    Halaman akan berpindah otomatis ke hasil rekomendasi.
                @endif
            </p>
            <noscript><p class="mt-4 text-xs text-ink/50">Halaman ini memuat ulang otomatis setiap beberapa detik.</p></noscript>
        </section>

        {{-- Gagal --}}
        <section class="card p-8 sm:p-10 text-center" x-show="status === 'failed'" @unless ($failed) x-cloak @endunless
                 x-ref="failure" tabindex="-1" role="alert" aria-labelledby="judul-gagal">
            <div class="mx-auto w-16 h-16 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center">
                <svg class="w-8 h-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
            </div>
            <h1 id="judul-gagal" class="mt-5 text-2xl font-extrabold text-ink">Permintaan belum berhasil</h1>
            <p class="mt-2 text-sm text-ink/70" x-text="error">{{ $task->error }}</p>

            <div class="mt-8 flex flex-col sm:flex-row gap-2 justify-center">
                <form action="{{ route('ai-tasks.retry', $task) }}" method="POST" data-submit-once>
                    @csrf
                    <button type="submit" class="btn-primary w-full sm:w-auto">Coba lagi</button>
                </form>
                <a href="{{ route('ai-tasks.edit', $task) }}" class="btn-secondary">Ubah preferensi</a>
            </div>
        </section>
    </div>
</x-app-layout>
