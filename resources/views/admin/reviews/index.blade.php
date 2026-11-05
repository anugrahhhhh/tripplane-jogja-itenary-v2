<x-admin-layout>
    <x-slot name="header">
        <h1 class="text-2xl sm:text-3xl font-extrabold text-ink">Moderasi Ulasan</h1>
        <p class="text-sm text-ink/60 mt-1">{{ $reviews->total() }} ulasan total.</p>
    </x-slot>

    <div class="mb-6 inline-flex rounded-xl bg-white p-1 ring-1 ring-primary-100 shadow-sm">
        @foreach (['' => 'Semua', 'tampil' => 'Tampil', 'sembunyi' => 'Disembunyikan'] as $value => $label)
            @php $isCurrent = (string) request('status', '') === (string) $value; @endphp
            <a href="{{ $value === '' ? route('admin.reviews.index') : route('admin.reviews.index', ['status' => $value]) }}"
               class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $isCurrent ? 'bg-primary text-white shadow-sm' : 'text-ink/60 hover:text-ink' }}">{{ $label }}</a>
        @endforeach
    </div>

    <div class="space-y-3">
        @forelse ($reviews as $review)
            <div class="card p-5 {{ $review->is_visible ? '' : 'bg-red-50/30' }}">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div class="flex items-start gap-3 min-w-0 flex-1">
                        <span class="w-10 h-10 shrink-0 rounded-full bg-gradient-to-br from-primary-100 to-accent-100 text-primary-700 flex items-center justify-center text-sm font-bold">
                            {{ strtoupper(substr($review->user->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0">
                            <p class="font-semibold text-ink text-sm">{{ $review->user->name }} &rarr; {{ $review->destination->name }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <div class="flex items-center gap-0.5">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <svg class="w-3.5 h-3.5 {{ $i <= $review->rating ? 'text-amber-400' : 'text-ink/15' }}" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.286 3.957a1 1 0 00.95.69h4.162c.969 0 1.371 1.24.588 1.81l-3.368 2.447a1 1 0 00-.363 1.118l1.287 3.957c.3.922-.755 1.688-1.538 1.118l-3.367-2.447a1 1 0 00-1.176 0l-3.367 2.447c-.783.57-1.838-.196-1.538-1.118l1.287-3.957a1 1 0 00-.363-1.118L2.062 9.384c-.783-.57-.38-1.81.588-1.81h4.162a1 1 0 00.95-.69l1.286-3.957z" /></svg>
                                    @endfor
                                </div>
                                <span class="text-xs text-ink/40">{{ $review->created_at->translatedFormat('d F Y H:i') }}</span>
                            </div>
                            @if ($review->comment)
                                <p class="mt-2 text-sm text-ink/70 leading-relaxed">{{ $review->comment }}</p>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        <span class="{{ $review->is_visible ? 'badge-primary' : 'badge-danger' }}">
                            {{ $review->is_visible ? 'Tampil' : 'Disembunyikan' }}
                        </span>
                        <form action="{{ route('admin.reviews.update', $review) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="btn-secondary btn-sm">
                                {{ $review->is_visible ? 'Sembunyikan' : 'Tampilkan' }}
                            </button>
                        </form>
                        <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" onsubmit="return confirm('Hapus ulasan ini secara permanen?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-danger btn-sm">Hapus</button>
                        </form>
                    </div>
                </div>
            </div>
        @empty
            <div class="empty-state">
                <span class="icon-tile w-14 h-14 mb-4">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" /></svg>
                </span>
                <p class="text-ink/50">Tidak ada ulasan pada filter ini.</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $reviews->links() }}
    </div>
</x-admin-layout>
