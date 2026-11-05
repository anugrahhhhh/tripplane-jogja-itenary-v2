@props(['dark' => false, 'size' => 'md'])

@php
    $text = $size === 'lg' ? 'text-2xl' : 'text-xl';
    $mark = $size === 'lg' ? 'w-10 h-10' : 'w-9 h-9';
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2.5']) }}>
    <span class="{{ $mark }} shrink-0 rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 text-white flex items-center justify-center shadow-glow">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" /></svg>
    </span>
    <span class="{{ $text }} font-display font-extrabold tracking-tight {{ $dark ? 'text-white' : 'text-primary-700' }}">Jogja<span class="{{ $dark ? 'text-accent-300' : 'text-accent-500' }}">Trip</span></span>
</span>
