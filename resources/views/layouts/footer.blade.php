<footer class="relative mt-24 bg-primary-900 text-primary-100 overflow-hidden">
    {{-- Ornamen latar --}}
    <div aria-hidden="true" class="pointer-events-none absolute inset-0">
        <div class="absolute -top-32 -right-24 w-96 h-96 rounded-full bg-primary-600/30 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-24 w-[28rem] h-[28rem] rounded-full bg-accent-500/10 blur-3xl"></div>
        <svg class="absolute inset-0 w-full h-full opacity-[0.04]" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <pattern id="footer-pattern" width="32" height="32" patternUnits="userSpaceOnUse">
                    <path d="M16 0l16 16-16 16L0 16z" fill="none" stroke="white" stroke-width="1" />
                    <circle cx="16" cy="16" r="2" fill="white" />
                </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#footer-pattern)" />
        </svg>
    </div>

    <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        {{-- CTA --}}
        <div class="pt-12">
            <div class="rounded-3xl bg-gradient-to-br from-primary-600 to-primary-700 ring-1 ring-white/10 px-6 py-8 sm:px-10 flex flex-col md:flex-row md:items-center md:justify-between gap-6 shadow-xl shadow-black/20">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-widest text-accent-200">Siap berangkat?</p>
                    <h2 class="mt-1 text-2xl sm:text-3xl font-bold text-white">Susun liburan impianmu di Jogja</h2>
                    <p class="mt-2 text-sm text-primary-100/80 max-w-xl">Pilih destinasi, atur jadwal per hari, dan ajak teman ikut merencanakan &mdash; semuanya di satu tempat.</p>
                </div>
                <div class="flex flex-wrap gap-3 shrink-0">
                    @auth
                        <a href="{{ route('itineraries.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-primary-700 hover:bg-primary-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            Buat Itinerary
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-semibold text-primary-700 hover:bg-primary-50 transition">
                            Daftar Gratis
                        </a>
                    @endauth
                    <a href="{{ route('recommendations.create') }}" class="inline-flex items-center gap-2 rounded-xl bg-white/10 ring-1 ring-white/20 px-5 py-3 text-sm font-semibold text-white hover:bg-white/20 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z" /></svg>
                        Coba Rekomendasi AI
                    </a>
                </div>
            </div>
        </div>

        {{-- Kolom tautan --}}
        <div class="grid grid-cols-2 lg:grid-cols-12 gap-x-8 gap-y-10 py-14">
            <div class="col-span-2 lg:col-span-4">
                <a href="{{ route('home') }}" class="inline-block" aria-label="JogjaTrip — Beranda"><x-brand dark size="lg" /></a>
                <p class="mt-3 text-sm leading-relaxed text-primary-200/80 max-w-sm">Rencanakan liburanmu ke Yogyakarta &mdash; dari rekomendasi tempat sampai jadwal itinerary, semua dalam satu aplikasi.</p>
                <ul class="mt-5 flex flex-wrap gap-2">
                    <li class="inline-flex items-center gap-1.5 rounded-full bg-white/5 ring-1 ring-white/10 px-3 py-1 text-xs text-primary-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent-300"></span> Rekomendasi AI
                    </li>
                    <li class="inline-flex items-center gap-1.5 rounded-full bg-white/5 ring-1 ring-white/10 px-3 py-1 text-xs text-primary-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-primary-300"></span> Itinerary Kolaboratif
                    </li>
                    <li class="inline-flex items-center gap-1.5 rounded-full bg-white/5 ring-1 ring-white/10 px-3 py-1 text-xs text-primary-100">
                        <span class="w-1.5 h-1.5 rounded-full bg-accent-200"></span> Peta &amp; Terdekat
                    </li>
                </ul>
            </div>

            <nav class="lg:col-span-2" aria-label="Jelajahi">
                <p class="text-sm font-semibold text-white">Jelajahi</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    <li><a href="{{ route('home') }}" class="group inline-flex items-center gap-1.5 text-primary-200/80 hover:text-white transition"><span class="w-0 group-hover:w-2 h-px bg-accent-300 transition-all"></span>Beranda</a></li>
                    <li><a href="{{ route('destinations.index') }}" class="group inline-flex items-center gap-1.5 text-primary-200/80 hover:text-white transition"><span class="w-0 group-hover:w-2 h-px bg-accent-300 transition-all"></span>Katalog Destinasi</a></li>
                    <li><a href="{{ route('nearby.index') }}" class="group inline-flex items-center gap-1.5 text-primary-200/80 hover:text-white transition"><span class="w-0 group-hover:w-2 h-px bg-accent-300 transition-all"></span>Destinasi Terdekat</a></li>
                    <li><a href="{{ route('recommendations.create') }}" class="group inline-flex items-center gap-1.5 text-primary-200/80 hover:text-white transition"><span class="w-0 group-hover:w-2 h-px bg-accent-300 transition-all"></span>Rekomendasi AI</a></li>
                </ul>
            </nav>

            <nav class="lg:col-span-2" aria-label="Akun">
                <p class="text-sm font-semibold text-white">Akun</p>
                <ul class="mt-4 space-y-2.5 text-sm">
                    @auth
                        <li><a href="{{ route('itineraries.index') }}" class="group inline-flex items-center gap-1.5 text-primary-200/80 hover:text-white transition"><span class="w-0 group-hover:w-2 h-px bg-accent-300 transition-all"></span>Itinerary Saya</a></li>
                        <li><a href="{{ route('favorites.index') }}" class="group inline-flex items-center gap-1.5 text-primary-200/80 hover:text-white transition"><span class="w-0 group-hover:w-2 h-px bg-accent-300 transition-all"></span>Favorit</a></li>
                        <li><a href="{{ route('notifications.index') }}" class="group inline-flex items-center gap-1.5 text-primary-200/80 hover:text-white transition"><span class="w-0 group-hover:w-2 h-px bg-accent-300 transition-all"></span>Notifikasi</a></li>
                        <li><a href="{{ route('profile.edit') }}" class="group inline-flex items-center gap-1.5 text-primary-200/80 hover:text-white transition"><span class="w-0 group-hover:w-2 h-px bg-accent-300 transition-all"></span>Profil</a></li>
                    @else
                        <li><a href="{{ route('login') }}" class="group inline-flex items-center gap-1.5 text-primary-200/80 hover:text-white transition"><span class="w-0 group-hover:w-2 h-px bg-accent-300 transition-all"></span>Masuk</a></li>
                        <li><a href="{{ route('register') }}" class="group inline-flex items-center gap-1.5 text-primary-200/80 hover:text-white transition"><span class="w-0 group-hover:w-2 h-px bg-accent-300 transition-all"></span>Daftar</a></li>
                    @endauth
                </ul>
            </nav>

            <div class="col-span-2 lg:col-span-4">
                <p class="text-sm font-semibold text-white">Info Wisata Jogja</p>
                <ul class="mt-4 space-y-3 text-sm">
                    <li class="flex gap-3">
                        <span class="shrink-0 w-9 h-9 rounded-xl bg-white/5 ring-1 ring-white/10 flex items-center justify-center text-accent-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z" /></svg>
                        </span>
                        <span>
                            <span class="block text-white">Daerah Istimewa Yogyakarta</span>
                            <span class="block text-xs text-primary-200/70">Kota, Sleman, Bantul, Gunungkidul &amp; Kulon Progo</span>
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="shrink-0 w-9 h-9 rounded-xl bg-white/5 ring-1 ring-white/10 flex items-center justify-center text-accent-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </span>
                        <span>
                            <span class="block text-white">Tips berkunjung</span>
                            <span class="block text-xs text-primary-200/70">Datang pagi hari ke tempat populer agar terhindar dari keramaian.</span>
                        </span>
                    </li>
                    <li class="flex gap-3">
                        <span class="shrink-0 w-9 h-9 rounded-xl bg-white/5 ring-1 ring-white/10 flex items-center justify-center text-accent-300">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z" /></svg>
                        </span>
                        <span>
                            <span class="block text-white">Nomor darurat</span>
                            <span class="block text-xs text-primary-200/70">Hubungi <a href="tel:112" class="font-semibold text-accent-200 hover:text-white">112</a> untuk keadaan darurat.</span>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Bar bawah --}}
        <div class="border-t border-white/10 py-6 flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-4">
            <p class="text-xs text-primary-300">&copy; {{ date('Y') }} JogjaTrip. Dibuat dengan <span class="text-accent-300">&hearts;</span> untuk membantu rencana wisata Yogyakarta.</p>
            <button type="button" onclick="window.scrollTo({ top: 0, behavior: 'smooth' })" class="self-start sm:self-auto inline-flex items-center gap-2 rounded-full bg-white/5 ring-1 ring-white/10 px-4 py-2 text-xs font-medium text-primary-100 hover:bg-white/10 hover:text-white transition">
                Kembali ke atas
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
            </button>
        </div>
    </div>
</footer>
