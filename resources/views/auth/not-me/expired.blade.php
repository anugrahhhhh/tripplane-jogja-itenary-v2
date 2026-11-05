<x-guest-layout>
    <h1 class="text-lg font-semibold text-ink">Link kedaluwarsa</h1>
    <p class="mt-2 text-sm text-ink/60">
        Link ini tidak valid atau sudah lebih dari 24 jam. Jika kamu merasa akunmu diakses orang lain,
        segera atur ulang kata sandi lewat halaman lupa kata sandi.
    </p>

    <div class="flex items-center justify-end mt-6">
        <a href="{{ route('password.request') }}" class="w-full inline-flex justify-center items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
            Atur ulang kata sandi
        </a>
    </div>
</x-guest-layout>
