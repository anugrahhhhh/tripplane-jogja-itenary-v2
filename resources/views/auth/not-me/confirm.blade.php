<x-guest-layout>
    <div class="mb-4 text-sm text-ink/60">
        Kamu menerima ini karena ada login baru ke akun JogjaTrip dengan email <strong>{{ $user->email }}</strong>.
        Jika itu bukan kamu, kami akan mengeluarkan semua sesi, mencabut perangkat tepercaya, mereset autentikasi dua langkah,
        lalu mengarahkanmu untuk membuat kata sandi baru.
    </div>

    <form method="POST" action="{{ request()->fullUrl() }}">
        @csrf

        <div class="flex items-center justify-end mt-6">
            <x-primary-button class="w-full justify-center">
                Ya, amankan akun saya
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
