{{-- Kartu kesehatan sistem (availability) — data dari App\Services\SystemHealth. --}}
@php
    $overall = [
        'ok' => ['Semua normal', 'bg-primary-50 text-primary-700 ring-primary-200'],
        'degraded' => ['Perlu perhatian', 'bg-amber-50 text-amber-800 ring-amber-200'],
        'down' => ['Gangguan', 'bg-red-50 text-red-700 ring-red-200'],
    ][$health['status']];
    $labels = [
        'database' => 'Database', 'database_replica' => 'Replika database', 'circuit_breakers' => 'Layanan eksternal', 'cache' => 'Cache', 'storage' => 'Penyimpanan',
        'queue' => 'Antrean', 'queue_worker' => 'Worker antrean', 'scheduler' => 'Scheduler', 'backup' => 'Backup database',
    ];
    $dots = ['ok' => 'bg-primary-500', 'warning' => 'bg-amber-500', 'critical' => 'bg-red-600'];
    $texts = ['ok' => 'Normal', 'warning' => 'Peringatan', 'critical' => 'Kritis'];
@endphp
<section class="mt-8 card p-6" aria-labelledby="judul-kesehatan">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 id="judul-kesehatan" class="font-bold text-ink">Kesehatan Sistem</h2>
            <p class="text-xs text-ink/50 mt-0.5">Diperiksa {{ \Illuminate\Support\Carbon::parse($health['checked_at'])->translatedFormat('d M Y, H:i:s') }}</p>
        </div>
        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $overall[1] }}">{{ $overall[0] }}</span>
    </div>

    <ul class="mt-5 grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3">
        @foreach ($health['checks'] as $name => $check)
            <li class="rounded-xl bg-cream/60 ring-1 ring-primary-100/70 px-4 py-3">
                <p class="flex items-center gap-2 text-sm font-semibold text-ink">
                    <span class="w-2.5 h-2.5 rounded-full {{ $dots[$check['status']] ?? 'bg-ink/30' }}" aria-hidden="true"></span>
                    {{ $labels[$name] ?? $name }}
                    <span class="sr-only">: {{ $texts[$check['status']] ?? $check['status'] }}</span>
                </p>
                <p class="mt-1 text-xs text-ink/60">{{ $check['message'] }}</p>
            </li>
        @endforeach
    </ul>
</section>
