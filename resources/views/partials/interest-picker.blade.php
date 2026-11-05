{{-- Pilihan minat kategori (checkbox name="interests[]"). Butuh $categories. --}}
<div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
    @foreach ($categories as $category)
        <label class="group relative flex items-center gap-2.5 rounded-xl border border-primary-100 bg-white px-3 py-2.5 text-sm cursor-pointer hover:border-primary-300 hover:bg-primary-50/50 transition has-[:checked]:bg-primary-50 has-[:checked]:border-primary-400 has-[:checked]:ring-2 has-[:checked]:ring-primary-500/15">
            <input type="checkbox" name="interests[]" value="{{ $category->name }}" class="peer sr-only" @checked(in_array($category->name, (array) old('interests', [])))>
            <span class="w-8 h-8 shrink-0 rounded-lg bg-primary-50 text-primary-600 flex items-center justify-center transition peer-checked:bg-primary peer-checked:text-white">
                <x-category-icon :slug="$category->slug" class="w-4 h-4" />
            </span>
            <span class="flex-1 text-ink/80 font-medium leading-tight">{{ $category->name }}</span>
            <svg class="w-4 h-4 shrink-0 text-primary opacity-0 peer-checked:opacity-100 transition" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
        </label>
    @endforeach
</div>
