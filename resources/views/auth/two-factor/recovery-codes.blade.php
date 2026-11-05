<x-guest-layout>
    <h1 class="text-2xl font-extrabold text-ink">Simpan kode pemulihan</h1>
    <p class="mt-2 mb-4 text-sm text-ink/60">
        Jika kehilangan akses ke aplikasi authenticator, kamu bisa masuk dengan salah satu kode ini.
        Setiap kode hanya berlaku sekali dan <strong>tidak akan ditampilkan lagi</strong>.
    </p>

    <ul id="recovery-codes" class="grid grid-cols-2 gap-2 rounded-2xl bg-cream ring-1 ring-primary-100 p-4 font-mono text-sm text-ink">
        @foreach ($codes as $code)
            <li class="rounded-lg bg-white px-3 py-2 text-center ring-1 ring-primary-100 tracking-wider">{{ $code }}</li>
        @endforeach
    </ul>

    <div class="mt-4 flex gap-3" x-data="{
        copied: false,
        text() { return [...document.querySelectorAll('#recovery-codes li')].map(li => li.textContent.trim()).join('\n'); },
        async copy() {
            try { await navigator.clipboard.writeText(this.text()); this.copied = true; setTimeout(() => this.copied = false, 2000); } catch (e) {}
        },
        download() {
            const url = URL.createObjectURL(new Blob([this.text() + '\n'], { type: 'text/plain' }));
            const a = document.createElement('a');
            a.href = url;
            a.download = 'jogjatrip-recovery-codes.txt';
            a.click();
            URL.revokeObjectURL(url);
        }
    }">
        <button type="button" @click="copy()" class="btn-secondary btn-sm">
            <span x-text="copied ? 'Tersalin' : 'Salin'">Salin</span>
        </button>
        <button type="button" @click="download()" class="btn-secondary btn-sm">Unduh .txt</button>
    </div>

    <form method="POST" action="{{ route('two-factor.recovery-codes.ack') }}" class="flex justify-end mt-6">
        @csrf
        <x-primary-button>
            Saya sudah menyimpannya
        </x-primary-button>
    </form>
</x-guest-layout>
