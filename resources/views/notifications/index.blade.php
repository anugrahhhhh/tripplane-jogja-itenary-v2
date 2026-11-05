<x-app-layout>
    <x-slot name="header">
        <div class="flex items-end justify-between flex-wrap gap-4">
            <div class="flex items-center gap-4">
                <span class="w-14 h-14 rounded-2xl bg-white shadow-soft ring-1 ring-primary-100 flex items-center justify-center text-primary-600">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                </span>
                <div>
                    <h1 class="text-3xl sm:text-4xl font-extrabold text-ink">Notifikasi</h1>
                    @php $unreadCount = auth()->user()->unreadNotifications->count(); @endphp
                    <p class="text-sm text-ink/60 mt-1">{{ $unreadCount > 0 ? $unreadCount.' belum dibaca' : 'Semua sudah dibaca' }}</p>
                </div>
            </div>
            @if (auth()->user()->unreadNotifications->isNotEmpty())
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-secondary">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                        Tandai semua dibaca
                    </button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        @if ($notifications->isEmpty())
            <div class="empty-state">
                <span class="icon-tile w-14 h-14 mb-4">
                    <svg class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9.143 17.082a24.248 24.248 0 003.844.148m-3.844-.148a23.856 23.856 0 01-5.455-1.31 8.964 8.964 0 002.3-5.542m3.155 6.852a3 3 0 005.667 1.97m1.965-2.277L21 21m-4.225-4.225a23.81 23.81 0 003.536-1.003A8.967 8.967 0 0118 9.75V9A6 6 0 006.53 6.53m10.245 10.245L6.53 6.53M3 3l3.53 3.53" /></svg>
                </span>
                <p class="font-semibold text-ink">Belum ada notifikasi.</p>
                <p class="mt-1 text-sm text-ink/50">Undangan kolaborasi dan kabar itinerary akan muncul di sini.</p>
            </div>
        @else
            <div class="card divide-y divide-primary-50 overflow-hidden">
                @foreach ($notifications as $notification)
                    <div class="relative {{ $notification->read_at ? '' : 'bg-accent-50/40' }}">
                        <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="w-full text-left px-5 py-4 transition hover:bg-primary-50/60">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 w-10 h-10 shrink-0 rounded-full flex items-center justify-center {{ $notification->read_at ? 'bg-ink/5 text-ink/40' : 'bg-accent-100 text-accent-600' }}">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                                    </span>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-ink {{ $notification->read_at ? 'text-ink/70' : 'font-semibold' }}">{{ $notification->data['message'] ?? 'Notifikasi' }}</p>
                                        <p class="mt-0.5 text-xs text-ink/40">{{ $notification->created_at->diffForHumans() }}</p>
                                    </div>
                                    @unless ($notification->read_at)
                                        <span class="w-2.5 h-2.5 rounded-full bg-accent-500 mt-2 shrink-0" aria-label="Belum dibaca"></span>
                                    @endunless
                                </div>
                            </button>
                        </form>
                        @if (! empty($notification->data['action_url']))
                            <div class="px-5 pb-4 -mt-2 pl-[4.25rem]">
                                <a href="{{ $notification->data['action_url'] }}" class="btn-secondary btn-sm">{{ $notification->data['action_label'] ?? 'Buka' }} &rarr;</a>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
