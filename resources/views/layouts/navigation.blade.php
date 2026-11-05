@php
    $navItems = [
        ['route' => 'home', 'pattern' => 'home', 'label' => 'Beranda', 'auth' => false],
        ['route' => 'destinations.index', 'pattern' => 'destinations.*', 'label' => 'Katalog', 'auth' => false],
        ['route' => 'nearby.index', 'pattern' => 'nearby.*', 'label' => 'Terdekat', 'auth' => false],
        ['route' => 'recommendations.create', 'pattern' => 'recommendations.*', 'label' => 'Rekomendasi AI', 'auth' => false],
        ['route' => 'favorites.index', 'pattern' => 'favorites.*', 'label' => 'Favorit', 'auth' => true],
        ['route' => 'itineraries.index', 'pattern' => 'itineraries.*', 'label' => 'Itinerary', 'auth' => true],
    ];
@endphp

<nav x-data="{ open: false, scrolled: false }"
     x-init="scrolled = window.scrollY > 8"
     @scroll.window="scrolled = window.scrollY > 8"
     @keydown.escape.window="open = false"
     :class="scrolled ? 'shadow-soft border-primary-100' : 'border-transparent'"
     class="sticky top-0 z-40 border-b border-primary-100/60 bg-white/85 backdrop-blur-xl transition-shadow duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16 sm:h-[4.5rem]">
            <div class="flex items-center gap-8">
                <a href="{{ route('home') }}" aria-label="JogjaTrip — Beranda">
                    <x-brand />
                </a>

                <div class="hidden lg:flex lg:items-center gap-1">
                    @foreach ($navItems as $item)
                        @if (! $item['auth'] || auth()->check())
                            <x-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">{{ $item['label'] }}</x-nav-link>
                        @endif
                    @endforeach
                </div>
            </div>

            <div class="hidden lg:flex lg:items-center gap-2">
                @auth
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="btn-sm btn bg-primary-900 text-white hover:bg-primary-800">
                            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                            Panel Admin
                        </a>
                    @endif

                    @php $unreadNotifications = auth()->user()->unreadNotifications; @endphp
                    <x-dropdown align="right" width="w-80">
                        <x-slot name="trigger">
                            <button type="button" aria-label="Notifikasi" class="relative w-10 h-10 rounded-full bg-white ring-1 ring-primary-100 hover:ring-primary-300 hover:bg-primary-50 transition flex items-center justify-center text-ink/60 hover:text-primary-700">
                                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                                @if ($unreadNotifications->isNotEmpty())
                                    <span class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-accent-500 text-white text-[10px] font-bold flex items-center justify-center ring-2 ring-white">
                                        {{ $unreadNotifications->count() > 9 ? '9+' : $unreadNotifications->count() }}
                                    </span>
                                @endif
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <div class="px-4 py-3 border-b border-primary-50 flex items-center justify-between">
                                <p class="text-sm font-bold text-ink">Notifikasi</p>
                                @if ($unreadNotifications->isNotEmpty())
                                    <form action="{{ route('notifications.read-all') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-xs font-semibold text-accent-500 hover:underline">Tandai semua dibaca</button>
                                    </form>
                                @endif
                            </div>
                            <div class="max-h-80 overflow-y-auto">
                                @forelse (auth()->user()->notifications()->latest()->limit(8)->get() as $notification)
                                    <form action="{{ route('notifications.read', $notification->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-3 hover:bg-primary-50/70 transition flex items-start gap-3 {{ $notification->read_at ? '' : 'bg-accent-50/50' }}">
                                            <span class="mt-0.5 w-8 h-8 shrink-0 rounded-full flex items-center justify-center {{ $notification->read_at ? 'bg-ink/5 text-ink/40' : 'bg-accent-100 text-accent-600' }}">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="text-xs text-ink {{ $notification->read_at ? 'text-ink/60' : 'font-semibold' }} line-clamp-2">{{ $notification->data['message'] ?? 'Notifikasi' }}</p>
                                                <p class="mt-0.5 text-[11px] text-ink/40">{{ $notification->created_at->diffForHumans() }}</p>
                                            </div>
                                            @unless ($notification->read_at)
                                                <span class="w-2 h-2 rounded-full bg-accent-500 mt-2 shrink-0"></span>
                                            @endunless
                                        </button>
                                    </form>
                                @empty
                                    <div class="px-4 py-8 text-center">
                                        <p class="text-sm font-medium text-ink/60">Belum ada notifikasi.</p>
                                        <p class="mt-0.5 text-xs text-ink/40">Kabar terbaru akan muncul di sini.</p>
                                    </div>
                                @endforelse
                            </div>
                            <div class="border-t border-primary-50 p-1">
                                <a href="{{ route('notifications.index') }}" class="block rounded-lg px-3 py-2 text-center text-sm font-semibold text-primary-700 hover:bg-primary-50 transition">Lihat semua notifikasi</a>
                            </div>
                        </x-slot>
                    </x-dropdown>

                    <x-dropdown align="right" width="w-60">
                        <x-slot name="trigger">
                            <button type="button" class="flex items-center gap-2 rounded-full pl-1 pr-3 py-1 bg-white ring-1 ring-primary-100 hover:ring-primary-300 transition">
                                <span class="w-8 h-8 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 text-white flex items-center justify-center text-sm font-bold">
                                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                                </span>
                                <span class="text-sm font-medium text-ink max-w-[9rem] truncate">{{ auth()->user()->name }}</span>
                                <svg class="w-4 h-4 text-ink/40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </x-slot>
                        <x-slot name="content">
                            <div class="px-4 py-3 border-b border-primary-50">
                                <p class="text-xs text-ink/50">Masuk sebagai</p>
                                <p class="text-sm font-semibold text-ink truncate">{{ auth()->user()->email }}</p>
                            </div>
                            <div class="p-1">
                                <x-dropdown-link :href="route('profile.edit')">
                                    <svg class="w-4 h-4 text-ink/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                                    Profil Saya
                                </x-dropdown-link>
                                <x-dropdown-link :href="route('itineraries.index')">
                                    <svg class="w-4 h-4 text-ink/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                                    Itinerary Saya
                                </x-dropdown-link>
                            </div>
                            <div class="p-1 border-t border-primary-50">
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <x-dropdown-link :href="route('logout')" class="!text-red-600 hover:!bg-red-50"
                                            onclick="event.preventDefault(); this.closest('form').submit();">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" /></svg>
                                        Keluar
                                    </x-dropdown-link>
                                </form>
                            </div>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="btn-ghost">Masuk</a>
                    <a href="{{ route('register') }}" class="btn-primary">Daftar Gratis</a>
                @endauth
            </div>

            <div class="flex items-center gap-2 lg:hidden">
                @auth
                    <a href="{{ route('notifications.index') }}" aria-label="Notifikasi" class="relative w-10 h-10 rounded-full bg-white ring-1 ring-primary-100 flex items-center justify-center text-ink/60">
                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                        @if (auth()->user()->unreadNotifications->isNotEmpty())
                            <span class="absolute top-1.5 right-1.5 w-2.5 h-2.5 rounded-full bg-accent-500 ring-2 ring-white"></span>
                        @endif
                    </a>
                @endauth
                <button type="button" @click="open = ! open" :aria-expanded="open" aria-label="Buka menu" class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-white ring-1 ring-primary-100 text-ink/70 hover:bg-primary-50 hover:text-primary transition">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    {{-- Menu mobile --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
         class="lg:hidden border-t border-primary-100 bg-white shadow-lift max-h-[calc(100vh-4rem)] overflow-y-auto">
        <div class="px-4 pt-3 pb-3 space-y-1">
            @foreach ($navItems as $item)
                @if (! $item['auth'] || auth()->check())
                    <x-responsive-nav-link :href="route($item['route'])" :active="request()->routeIs($item['pattern'])">{{ $item['label'] }}</x-responsive-nav-link>
                @endif
            @endforeach
            @auth
                <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                    Notifikasi
                    @if (auth()->user()->unreadNotifications->isNotEmpty())
                        <span class="ml-auto inline-flex items-center rounded-full bg-accent-500 text-white text-[10px] font-bold px-1.5 py-0.5">{{ auth()->user()->unreadNotifications->count() }}</span>
                    @endif
                </x-responsive-nav-link>
                @if (auth()->user()->isAdmin())
                    <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">Panel Admin</x-responsive-nav-link>
                @endif
            @endauth
        </div>

        <div class="px-4 pt-3 pb-5 border-t border-primary-100">
            @auth
                <div class="flex items-center gap-3 rounded-2xl bg-cream p-3">
                    <span class="w-10 h-10 rounded-full bg-gradient-to-br from-primary-400 to-primary-600 text-white flex items-center justify-center text-sm font-bold">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <div class="min-w-0">
                        <div class="font-semibold text-ink truncate">{{ auth()->user()->name }}</div>
                        <div class="text-sm text-ink/50 truncate">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                <div class="mt-2 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')">Profil Saya</x-responsive-nav-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-responsive-nav-link :href="route('logout')" class="!text-red-600"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                            Keluar
                        </x-responsive-nav-link>
                    </form>
                </div>
            @else
                <div class="grid grid-cols-2 gap-2">
                    <a href="{{ route('login') }}" class="btn-secondary">Masuk</a>
                    <a href="{{ route('register') }}" class="btn-primary">Daftar</a>
                </div>
            @endauth
        </div>
    </div>
</nav>
