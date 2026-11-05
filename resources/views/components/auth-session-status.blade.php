@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert-success font-medium']) }}>
        <svg class="w-5 h-5 shrink-0 text-primary-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
        <span>{{ $status }}</span>
    </div>
@endif
