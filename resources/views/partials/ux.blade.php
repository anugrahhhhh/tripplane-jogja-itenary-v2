{{--
    Elemen UX bersama untuk semua layout (perilakunya di resources/js/ux.js):
    - dialog konfirmasi untuk form ber-atribut data-confirm="pesan",
    - banner saat perangkat offline.
--}}
<dialog id="dialog-konfirmasi" aria-labelledby="dialog-konfirmasi-judul" aria-describedby="dialog-konfirmasi-pesan"
        class="w-[calc(100%-2rem)] max-w-md rounded-3xl p-0 shadow-lift ring-1 ring-primary-100/70 backdrop:bg-ink/40 backdrop:backdrop-blur-sm">
    <form method="dialog" class="p-6 sm:p-7">
        <div class="flex items-start gap-4">
            <span data-confirm-icon class="w-11 h-11 shrink-0 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center" aria-hidden="true">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            </span>
            <div class="min-w-0">
                <h2 id="dialog-konfirmasi-judul" class="text-lg font-bold text-ink">Yakin ingin melanjutkan?</h2>
                <p id="dialog-konfirmasi-pesan" class="mt-1.5 text-sm text-ink/70"></p>
            </div>
        </div>
        <div class="mt-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
            <button type="submit" value="cancel" class="btn-secondary" autofocus>Batal</button>
            <button type="submit" value="confirm" data-confirm-ok class="btn bg-red-600 text-white shadow-sm hover:bg-red-700 focus-visible:ring-red-500/30">Ya, lanjutkan</button>
        </div>
    </form>
</dialog>

{{-- Pembungkus sengaja TANPA class display (flex/block): class seperti itu
     mengalahkan atribut [hidden] sehingga banner selalu tampil. --}}
<div id="banner-offline" hidden role="status" aria-live="polite" class="fixed inset-x-4 bottom-4 z-[90] mx-auto max-w-md">
  <div class="rounded-2xl bg-ink text-white text-sm shadow-lift px-4 py-3 flex items-center gap-3">
    <svg class="w-5 h-5 shrink-0 text-amber-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c.9-.9 1.946-1.6 3.075-2.1m3.82-.736a10.5 10.5 0 017.894 2.836M1.924 8.674c1.12-1.12 2.4-2.04 3.78-2.737M12 18.75h.008v.008H12v-.008z" /></svg>
    <p class="flex-1">Kamu sedang offline. Perubahan belum bisa disimpan sampai koneksi kembali.</p>
  </div>
</div>
