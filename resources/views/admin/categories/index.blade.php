<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-ink">Kelola Kategori</h1>
                <p class="text-sm text-ink/60 mt-1">Kelompokkan destinasi agar mudah dijelajahi wisatawan.</p>
            </div>
            <a href="{{ route('admin.categories.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Kategori
            </a>
        </div>
    </x-slot>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Slug</th>
                        <th>Jumlah Destinasi</th>
                        <th class="!text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>
                                <div class="flex items-center gap-3">
                                    <span class="icon-tile w-9 h-9">
                                        <x-category-icon :slug="$category->icon ?: $category->slug" class="w-4 h-4" />
                                    </span>
                                    <span class="font-semibold text-ink">{{ $category->name }}</span>
                                </div>
                            </td>
                            <td><code class="rounded-md bg-cream px-2 py-0.5 text-xs text-ink/60 ring-1 ring-primary-100">{{ $category->slug }}</code></td>
                            <td><span class="badge-primary">{{ $category->destinations_count }} destinasi</span></td>
                            <td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn-secondary btn-sm">Ubah</a>
                                    <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline" data-confirm="Hapus kategori ini?" data-confirm-title="Hapus data?" data-confirm-button="Ya, hapus">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="!py-14 text-center text-ink/50">Belum ada kategori.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $categories->links() }}
    </div>
</x-admin-layout>
