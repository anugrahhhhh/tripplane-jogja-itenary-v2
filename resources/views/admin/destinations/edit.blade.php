<x-admin-layout>
    <x-slot name="header">
        <a href="{{ route('admin.destinations.index') }}" class="inline-flex items-center gap-1 text-sm font-semibold text-ink/50 hover:text-primary-700">&larr; Kembali</a>
        <h1 class="mt-2 text-2xl sm:text-3xl font-extrabold text-ink">Ubah Destinasi</h1>
    </x-slot>

    <form action="{{ route('admin.destinations.update', $destination) }}" method="POST" enctype="multipart/form-data" class="card p-6 sm:p-8 space-y-5 max-w-4xl">
        @csrf
        @method('PUT')
        @include('admin.destinations._form')

        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2 pt-6 border-t border-primary-50">
            <a href="{{ route('admin.destinations.index') }}" class="btn-secondary">Batal</a>
            <x-primary-button>Simpan Perubahan</x-primary-button>
        </div>
    </form>
</x-admin-layout>
