@props(['active'])

@php
$classes = ($active ?? false)
            ? 'flex items-center gap-2 w-full rounded-xl px-4 py-2.5 text-start text-base font-semibold text-primary-700 bg-primary-50 transition duration-150 ease-in-out'
            : 'flex items-center gap-2 w-full rounded-xl px-4 py-2.5 text-start text-base font-medium text-ink/70 hover:text-primary-700 hover:bg-primary-50 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
