<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-ink">Link kedaluwarsa</h1>
    <p class="mt-2 text-sm text-ink/60">
        Link ini tidak valid atau sudah lebih dari 24 jam. Jika kamu merasa akunmu diakses orang lain,
        segera atur ulang kata sandi lewat halaman lupa kata sandi.
    </p>

    <div class="flex items-center justify-end mt-6">
        <a href="{{ route('password.request') }}" class="btn-primary w-full">
            Atur ulang kata sandi
        </a>
    </div>
</x-guest-layout>
