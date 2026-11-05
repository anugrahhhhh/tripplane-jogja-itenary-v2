<section class="space-y-6">
    <header>
        <h2 class="text-lg font-bold text-red-700">
            Hapus Akun
        </h2>

        <p class="mt-1 text-sm text-ink/60">
            Setelah akun dihapus, seluruh data (favorit, ulasan, itinerary) akan dihapus permanen. Pastikan kamu sudah menyimpan data yang ingin dipertahankan.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hapus Akun</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-lg font-bold text-ink">
                Yakin ingin menghapus akun?
            </h2>

            <p class="mt-1 text-sm text-ink/60">
                Setelah akun dihapus, seluruh data akan dihapus permanen. Masukkan kata sandimu untuk konfirmasi.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Kata Sandi" class="sr-only" />
                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Kata Sandi"
                />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Batal
                </x-secondary-button>

                <x-danger-button class="ms-3">
                    Hapus Akun
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
