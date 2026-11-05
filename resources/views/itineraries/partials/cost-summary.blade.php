@props(['cost', 'pdf' => false])
@php
    use App\Support\Rupiah;

    $totalMax = $cost['total']['max'];
    $isFree = $totalMax === 0 && $cost['total']['min'] === 0;
    $showGroup = $cost['travelers'] > 1 && $totalMax > 0;
@endphp
{{-- Tanpa konten yang bergantung pada auth: dipakai juga di halaman publik. --}}
<div @if ($pdf) style="margin-top: 14px; padding: 8px 10px; border: 1px solid #D4E5DF;" @else class="card p-5 sm:p-6 relative overflow-hidden" @endif>
    <h2 @if ($pdf) style="margin-top: 0; font-size: 14px; color: #2F6B5A; border: 0;" @else class="flex items-center gap-2 text-sm font-bold uppercase tracking-wide text-ink/50" @endif>Estimasi biaya</h2>
    <p @if ($pdf) style="margin: 4px 0; font-weight: bold;" @else class="mt-2 font-display text-xl sm:text-2xl font-extrabold text-primary-800" @endif>
        @if ($isFree)
            Gratis
        @else
            {{ Rupiah::range($cost['total']['min'], $totalMax) }} per orang
            @if ($showGroup)
                &middot; {{ Rupiah::range($cost['group_total']['min'], $cost['group_total']['max']) }} untuk {{ $cost['travelers'] }} orang
            @endif
        @endif
    </p>
    @if (! empty($cost['days']))
        <ul @if ($pdf) style="margin: 4px 0; padding-left: 16px;" @else class="mt-3 flex flex-wrap gap-2 text-xs text-ink/70" @endif>
            @foreach ($cost['days'] as $day => $range)
                <li @unless ($pdf) class="rounded-full bg-primary-50 ring-1 ring-primary-100 px-3 py-1 font-medium" @endunless>Hari {{ $day }}: {{ Rupiah::range($range['min'], $range['max']) }}</li>
            @endforeach
        </ul>
    @endif
    <p @if ($pdf) style="margin: 4px 0 0; font-size: 10px; color: #8a8a8a;" @else class="mt-3 text-xs text-ink/50" @endif>Estimasi tiket masuk saja, belum termasuk transportasi, makan, dan parkir.</p>
</div>
