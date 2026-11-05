<x-admin-layout>
    <x-slot name="header">
        <a href="{{ route('admin.categories.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-ink/50 hover:text-primary-700">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-ink">Tambah Kategori</h1>
    </x-slot>

    <form action="{{ route('admin.categories.store') }}" method="POST" class="card p-6 sm:p-8 space-y-4 max-w-4xl">
        @csrf
        @include('admin.categories._form')

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-6 border-t border-primary-50">
            <a href="{{ route('admin.categories.index') }}" class="btn-secondary">Batal</a>
            <x-primary-button>Simpan Kategori</x-primary-button>
        </div>
    </form>
</x-admin-layout>
