@props(['active'])

@php
$classes = ($active ?? false)
            ? 'relative inline-flex items-center rounded-full px-3.5 py-2 text-sm font-semibold text-primary-700 bg-primary-50 ring-1 ring-inset ring-primary-100 transition duration-150 ease-in-out'
            : 'relative inline-flex items-center rounded-full px-3.5 py-2 text-sm font-medium text-ink/60 hover:text-ink hover:bg-primary-50/60 transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if ($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
