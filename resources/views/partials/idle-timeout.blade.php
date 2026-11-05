{{-- Peringatan dan auto-logout saat tidak aktif; logikanya di resources/js/idle-timeout.js --}}
<div x-data="idleTimeout"
     data-timeout="{{ config('auth.idle_timeout') }}"
     data-ping-url="{{ route('session.ping') }}"
     data-login-url="{{ auth()->user()?->isAdmin() ? route('admin.login') : route('login') }}"
     data-csrf="{{ csrf_token() }}">
    <form id="idle-logout-form" x-ref="logoutForm" method="POST" action="{{ route('logout') }}" class="hidden">
        @csrf
        <input type="hidden" name="reason" value="idle">
    </form>

    <div x-show="open" x-cloak x-transition.opacity
         class="fixed inset-0 z-50 flex items-center justify-center px-4 bg-primary-900/60 backdrop-blur-sm"
         role="alertdialog" aria-modal="true" aria-live="assertive">
        <div class="w-full max-w-md bg-white rounded-3xl shadow-lift ring-1 ring-primary-100 p-8 text-center">
            <p class="text-lg font-semibold text-ink">
                Sesi akan berakhir dalam <span x-text="remaining"></span> detik
            </p>
            <p class="mt-2 text-sm text-ink/70">Kamu akan keluar otomatis karena tidak ada aktivitas.</p>
            <button type="button" x-ref="stayButton" @click="stay()"
                    class="mt-6 btn-primary px-6">
                Tetap masuk
            </button>
        </div>
    </div>
</div>
