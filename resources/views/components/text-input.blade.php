@props(['disabled' => false])

@php
    // Tandai field yang gagal validasi (aria-invalid + garis merah). Nama
    // seperti "items[0][notes]" diubah ke kunci error "items.0.notes".
    $errorKey = trim(str_replace(['[]', '][', '[', ']'], ['', '.', '.', ''], (string) $attributes->get('name')), '.');
    $invalid = $errorKey !== '' && isset($errors) && $errors->has($errorKey);
@endphp

<input @disabled($disabled) @if ($invalid) aria-invalid="true" @endif {{ $attributes->merge(['class' => 'w-full rounded-xl bg-white text-ink placeholder:text-ink/40 shadow-sm focus:ring-4 disabled:bg-cream disabled:text-ink/50 '.($invalid ? 'border-red-300 focus:border-red-400 focus:ring-red-500/10' : 'border-primary-100 focus:border-primary-400 focus:ring-primary-500/10')]) }}>
