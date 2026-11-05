<x-admin-layout>
    <x-slot name="header">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-ink">Data Pengguna</h1>
        <p class="text-sm text-ink/60 mt-1">{{ $users->total() }} pengguna terdaftar.</p>
    </x-slot>

    <form method="GET" class="card p-3 flex flex-col sm:flex-row gap-3 mb-6">
        <div class="relative flex-1">
            <svg class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-ink/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 10a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
            <label for="admin-user-search" class="sr-only">Cari nama atau email</label>
            <input id="admin-user-search" type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email..." class="input pl-10">
        </div>
        <button type="submit" class="btn-primary">Cari</button>
        @if (request('q'))
            <a href="{{ route('admin.users.index') }}" class="btn-ghost">Reset</a>
        @endif
    </form>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table-modern">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Role</th>
                        <th>Favorit</th>
                        <th>Ulasan</th>
                        <th>Itinerary</th>
                        <th class="!text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <span class="w-9 h-9 shrink-0 rounded-full {{ $user->role === 'admin' ? 'bg-gradient-to-br from-accent-300 to-accent-500 text-white' : 'bg-primary-50 text-primary-700' }} flex items-center justify-center text-xs font-bold">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-semibold text-ink">{{ $user->name }}</p>
                                        <p class="text-xs text-ink/50">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <span class="{{ $user->role === 'admin' ? 'badge-accent' : 'badge-primary' }}">
                                    {{ $user->role === 'admin' ? 'Admin' : 'Traveler' }}
                                </span>
                            </td>
                            <td class="text-ink/60 tabular-nums">{{ $user->favorites_count }}</td>
                            <td class="text-ink/60 tabular-nums">{{ $user->reviews_count }}</td>
                            <td class="text-ink/60 tabular-nums">{{ $user->itineraries_count }}</td>
                            <td class="text-right">
                                <a href="{{ route('admin.users.show', $user) }}" class="btn-secondary btn-sm">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="!py-14 text-center text-ink/50">Tidak ada pengguna ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $users->links() }}
    </div>
</x-admin-layout>
