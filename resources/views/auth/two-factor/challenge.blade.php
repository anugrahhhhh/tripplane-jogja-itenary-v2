<x-guest-layout>
    <div x-data="{ recovery: {{ $errors->has('code') && old('recovery_code') ? 'true' : 'false' }} }">
        <h1 class="text-lg font-semibold text-ink">Verifikasi dua langkah</h1>

        <form method="POST" action="{{ route('two-factor.challenge.store') }}" class="mt-4">
            @csrf

            <div x-show="!recovery">
                <p class="mb-3 text-sm text-ink/60">Masukkan kode 6 digit dari aplikasi authenticator-mu.</p>
                <x-input-label for="code" value="Kode 6 digit" />
                <x-text-input id="code" class="block mt-1 w-full tracking-widest" type="text" name="code"
                    inputmode="numeric" autocomplete="one-time-code" maxlength="10"
                    x-bind:disabled="recovery" autofocus />
            </div>

            <div x-show="recovery" x-cloak>
                <p class="mb-3 text-sm text-ink/60">Masukkan salah satu recovery code yang kamu simpan.</p>
                <x-input-label for="recovery_code" value="Recovery code" />
                <x-text-input id="recovery_code" class="block mt-1 w-full font-mono" type="text" name="recovery_code"
                    autocomplete="off" maxlength="64" x-bind:disabled="!recovery" />
            </div>

            @foreach ($errors->get('code') as $message)
                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
            @endforeach

            <label class="mt-4 inline-flex items-center gap-2 text-sm text-ink/60">
                <input type="checkbox" name="trust_device" value="1" class="rounded border-ink/20 text-primary focus:ring-primary">
                Percayai perangkat ini selama 30 hari
            </label>

            <div class="flex items-center justify-between mt-6">
                <button type="button" class="text-sm text-ink/60 underline hover:text-ink" x-on:click="recovery = !recovery">
                    <span x-show="!recovery">Pakai recovery code</span>
                    <span x-show="recovery" x-cloak>Pakai kode authenticator</span>
                </button>

                <x-primary-button>
                    Verifikasi
                </x-primary-button>
            </div>
        </form>

        <p class="mt-4 text-sm text-ink/60">
            <a href="{{ route('login') }}" class="underline hover:text-ink">Kembali ke halaman masuk</a>
        </p>
    </div>
</x-guest-layout>
