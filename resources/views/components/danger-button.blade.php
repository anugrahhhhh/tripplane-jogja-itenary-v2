<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn bg-red-600 text-white shadow-sm hover:bg-red-500 active:bg-red-700 focus-visible:ring-red-500/30']) }}>
    {{ $slot }}
</button>
