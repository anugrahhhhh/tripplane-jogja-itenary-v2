@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-xl border-primary-100 bg-white text-ink placeholder:text-ink/40 shadow-sm focus:border-primary-400 focus:ring-4 focus:ring-primary-500/10 disabled:bg-cream disabled:text-ink/50']) }}>
