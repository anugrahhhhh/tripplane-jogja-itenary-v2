<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between flex-wrap gap-3">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-ink">Kelola Destinasi</h1>
                <p class="text-sm text-ink/60 mt-1">{{ $destinations->total() }} destinasi terdaftar.</p>
            </div>
            <a href="{{ route('admin.destinations.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Tambah Destinasi
            </a>
        </div>
    </x-slot>

    <form method="GET" class="card p-3 flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-ink/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <label for="admin-destination-search" class="sr-only">Cari nama destinasi</label>
            <input id="admin-destination-search" type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama destinasi..." class="input pl-10">
        </div>
        <label for="admin-destination-category" class="sr-only">Kategori</label>
        <select id="admin-destination-category" name="kategori" class="input sm:w-56">
            <option value="">Semua Kategori</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('kategori') === (string) $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn-primary">Cari</button>
        @if (request('q') || request('kategori'))
            <a href="{{ route('admin.destinations.index') }}" class="btn-ghost">Reset</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Destinasi</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Status</th>
                        <th class="!text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($destinations as $destination)
                        <tr>
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <span class="w-11 h-11 shrink-0 rounded-xl overflow-hidden bg-primary-50 ring-1 ring-primary-100 flex items-center justify-center text-primary-300">
                                        @if ($destination->cover_photo)
                                            <img src="{{ asset('storage/'.$destination->cover_photo) }}" alt="" class="w-full h-full object-cover" loading="lazy" decoding="async">
                                        @else
                                            <x-category-icon :slug="$destination->category->slug" class="w-5 h-5" />
                                        @endif
                                    </span>
                                    <span class="font-semibold text-ink">{{ $destination->name }}</span>
                                </div>
                            </td>
                            <td class="text-ink/60 whitespace-nowrap">{{ $destination->category->name }}</td>
                            <td class="text-ink/60 whitespace-nowrap">
                                @if ($destination->price_min == 0 && $destination->price_max == 0)
                                    <span class="font-semibold text-primary-600">Gratis</span>
                                @else
                                    Rp{{ number_format($destination->price_min) }} - Rp{{ number_format($destination->price_max) }}
                                @endif
                            </td>
                            <td>
                                <span class="{{ $destination->is_active ? 'badge-primary' : 'badge-danger' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $destination->is_active ? 'bg-primary-500' : 'bg-red-500' }}"></span>
                                    {{ $destination->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td class="text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('destinations.show', $destination) }}" target="_blank" class="btn-ghost btn-sm" title="Lihat di situs">Lihat</a>
                                    <a href="{{ route('admin.destinations.edit', $destination) }}" class="btn-secondary btn-sm">Ubah</a>
                                    <form action="{{ route('admin.destinations.destroy', $destination) }}" method="POST" class="inline" onsubmit="return confirm('Hapus destinasi ini beserta seluruh foto, favorit, ulasan, dan riwayatnya?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger btn-sm">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="!py-14 text-center text-ink/50">Tidak ada destinasi ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $destinations->links() }}
    </div>
</x-admin-layout>
