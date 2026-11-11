@php
    $hiddenReviews = \App\Models\Review::where('is_visible', false)->count();
    $adminNav = [
        ['route' => 'admin.dashboard', 'pattern' => 'admin.dashboard', 'label' => 'Dashboard', 'icon' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z', 'badge' => null],
        ['route' => 'admin.categories.index', 'pattern' => 'admin.categories.*', 'label' => 'Kategori', 'icon' => 'M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z M6 6h.008v.008H6V6z', 'badge' => null],
        ['route' => 'admin.destinations.index', 'pattern' => 'admin.destinations.*', 'label' => 'Destinasi', 'icon' => 'M15 10.5a3 3 0 11-6 0 3 3 0 016 0z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z', 'badge' => null],
        ['route' => 'admin.reviews.index', 'pattern' => 'admin.reviews.*', 'label' => 'Moderasi Ulasan', 'icon' => 'M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z', 'badge' => $hiddenReviews ?: null],
        ['route' => 'admin.users.index', 'pattern' => 'admin.users.*', 'label' => 'Data Pengguna', 'icon' => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z', 'badge' => null],
    ];
@endphp
<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="theme-color" content="#0F241F">

        <title>{{ isset($title) ? $title.' - ' : '' }}Panel Admin - {{ config('app.name') }}</title>
        <link rel="icon" href="/icons/icon-192.png">

        @include('partials.fonts')

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        {{ $head ?? '' }}
    </head>
    <body class="font-sans antialiased bg-cream text-ink">
        <a href="#konten-utama" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[100] focus:rounded-xl focus:bg-primary-700 focus:px-4 focus:py-2 focus:text-sm focus:font-semibold focus:text-white focus:shadow-lift focus:outline-none focus:ring-2 focus:ring-accent-400">Lewati ke konten utama</a>
        <div x-data="{ sidebar: false }" @keydown.escape.window="sidebar = false" class="min-h-screen lg:flex">
            {{-- Latar gelap saat sidebar mobile terbuka --}}
            <div x-show="sidebar" x-cloak x-transition.opacity @click="sidebar = false" class="fixed inset-0 z-40 bg-primary-900/60 backdrop-blur-sm lg:hidden"></div>

            {{-- Sidebar --}}
            <aside :class="sidebar ? 'translate-x-0' : '-translate-x-full'"
                   class="fixed inset-y-0 left-0 z-50 w-72 -translate-x-full lg:translate-x-0 lg:sticky lg:top-0 lg:h-screen shrink-0 bg-primary-900 text-primary-100 flex flex-col transition-transform duration-200 overflow-hidden">
                <div aria-hidden="true" class="pointer-events-none absolute inset-0 pattern-dots opacity-40"></div>
                <div aria-hidden="true" class="pointer-events-none absolute -bottom-24 -left-20 w-72 h-72 rounded-full bg-primary-500/20 blur-3xl"></div>

                <div class="relative flex items-center justify-between px-6 h-20 shrink-0">
                    <a href="{{ route('admin.dashboard') }}" aria-label="Dashboard Admin">
                        <x-brand dark />
                    </a>
                    <button type="button" @click="sidebar = false" class="lg:hidden text-primary-200 hover:text-white" aria-label="Tutup menu">
                        <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <nav class="relative flex-1 overflow-y-auto px-4 py-2" aria-label="Menu admin">
                    <p class="px-3 pb-2 text-[10px] font-bold uppercase tracking-[0.2em] text-primary-300/60">Kelola</p>
                    <ul class="space-y-1">
                        @foreach ($adminNav as $item)
                            @php $isActive = request()->routeIs($item['pattern']); @endphp
                            <li>
                                <a href="{{ route($item['route']) }}" @if ($isActive) aria-current="page" @endif
                                   class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $isActive ? 'bg-white text-primary-800 shadow-lift' : 'text-primary-100/80 hover:bg-white/10 hover:text-white' }}">
                                    <span class="w-8 h-8 rounded-lg flex items-center justify-center {{ $isActive ? 'bg-primary-50 text-primary-600' : 'bg-white/5 text-primary-200 group-hover:text-white' }}">
                                        <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] }}" /></svg>
                                    </span>
                                    <span class="flex-1">{{ $item['label'] }}</span>
                                    @if ($item['badge'])
                                        <span class="min-w-[20px] h-5 px-1.5 rounded-full bg-accent-500 text-white text-[10px] font-bold flex items-center justify-center">{{ $item['badge'] > 99 ? '99+' : $item['badge'] }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>

                    <p class="px-3 pt-6 pb-2 text-[10px] font-bold uppercase tracking-[0.2em] text-primary-300/60">Situs</p>
                    <a href="{{ route('home') }}" class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-primary-100/80 hover:bg-white/10 hover:text-white transition">
                        <span class="w-8 h-8 rounded-lg bg-white/5 text-primary-200 group-hover:text-white flex items-center justify-center">
                            <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                        </span>
                        Lihat Situs
                    </a>
                </nav>

                <div class="relative p-4 border-t border-white/10">
                    <div class="flex items-center gap-3 rounded-2xl bg-white/5 ring-1 ring-white/10 p-3">
                        <span class="w-10 h-10 shrink-0 rounded-full bg-gradient-to-br from-accent-300 to-accent-500 text-white flex items-center justify-center text-sm font-bold">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-primary-200/70 truncate">{{ auth()->user()->email }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" title="Keluar" aria-label="Keluar" class="w-9 h-9 rounded-lg text-primary-200 hover:bg-white/10 hover:text-white flex items-center justify-center transition">
                                <svg class="w-[18px] h-[18px]" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                            </button>
                        </form>
                    </div>
                </div>
            </aside>

            {{-- Konten --}}
            <div class="flex-1 min-w-0 flex flex-col">
                <div class="sticky top-0 z-30 lg:hidden flex items-center justify-between gap-3 h-16 px-4 bg-white/85 backdrop-blur-xl border-b border-primary-100">
                    <button type="button" @click="sidebar = true" class="w-10 h-10 rounded-full ring-1 ring-primary-100 bg-white text-ink/70 flex items-center justify-center" aria-label="Buka menu">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" /></svg>
                    </button>
                    <a href="{{ route('admin.dashboard') }}"><x-brand /></a>
                    <span class="w-10"></span>
                </div>

                @isset($header)
                    <header class="relative z-10 bg-gradient-to-br from-white via-cream to-primary-50 border-b border-primary-100/70">
                        <div aria-hidden="true" class="pointer-events-none absolute inset-0 overflow-hidden">
                            <div class="absolute inset-0 pattern-dots-dark opacity-50"></div>
                        </div>
                        <div class="relative px-4 sm:px-8 py-7">
                            <p class="mb-1 text-[11px] font-bold uppercase tracking-[0.2em] text-accent-500">Panel Admin</p>
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <div class="px-4 sm:px-8">
                    @include('partials.flash')
                    @include('partials.recovery-codes-banner')
                </div>

                <main id="konten-utama" tabindex="-1" class="flex-1 px-4 sm:px-8 py-8 focus:outline-none">
                    {{ $slot }}
                </main>

                <footer class="px-4 sm:px-8 py-5 border-t border-primary-100/70 text-xs text-ink/40">
                    &copy; {{ date('Y') }} JogjaTrip &middot; Panel Admin
                </footer>
            </div>
        </div>

        @if (session('clear_page_cache'))
            @include('partials.clear-page-cache')
        @endif

        @include('partials.idle-timeout')
        @include('partials.ux')
    </body>
</html>
