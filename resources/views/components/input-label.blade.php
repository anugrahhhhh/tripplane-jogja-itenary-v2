@props(['value'])

<label {{ $attributes->merge(['class' => 'block font-semibold text-sm text-ink/80']) }}>
    {{ $value ?? $slot }}
</label>
