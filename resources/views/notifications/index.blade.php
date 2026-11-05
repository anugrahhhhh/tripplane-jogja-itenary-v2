<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-ink">Notifikasi</h1>
            @if (auth()->user()->unreadNotifications->isNotEmpty())
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-sm font-semibold text-accent-500 hover:underline">Tandai semua dibaca</button>
                </form>
            @endif
        </div>
    </x-slot>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if ($notifications->isEmpty())
            <div class="text-center py-16 bg-white rounded-2xl border border-primary-100">
                <p class="text-ink/60">Belum ada notifikasi.</p>
            </div>
        @else
            <div class="space-y-2">
                @foreach ($notifications as $notification)
                    <div>
                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                        @csrf
                        <button type="submit" class="w-full text-left rounded-xl border px-4 py-3 transition {{ $notification->read_at ? 'bg-white border-primary-100' : 'bg-primary-50 border-primary-200' }} hover:border-primary-300">
                            <div class="flex items-start gap-3">
                                @unless ($notification->read_at)
                                    <span class="w-2 h-2 rounded-full bg-accent-500 mt-1.5 shrink-0"></span>
                                @endunless
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-ink {{ $notification->read_at ? '' : 'font-semibold' }}">{{ $notification->data['message'] ?? 'Notifikasi' }}</p>
                                    <p class="mt-0.5 text-xs text-ink/40">{{ $notification->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        </button>
                    </form>
                    @if (! empty($notification->data['action_url']))
                        <a href="{{ $notification->data['action_url'] }}" class="mt-1 ml-4 inline-block text-sm font-semibold text-accent-500 hover:underline">{{ $notification->data['action_label'] ?? 'Buka' }}</a>
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
