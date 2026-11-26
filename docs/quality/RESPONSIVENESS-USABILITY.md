# Responsiveness & Usability — JogjaTrip

Penjelasan teknis dua atribut kualitas dari [ANALISIS-KUALITAS.md](../ANALISIS-KUALITAS.md):
cara kerjanya di kode, cara membuktikannya saat presentasi, dan apa yang belum tercakup.

---

## 1. Responsiveness

**Tujuan:** aplikasi langsung memberi respons ke pengguna, walaupun pekerjaan di
belakangnya lama atau layanan luar sedang lambat.

### 1.1 Permintaan AI diproses di latar belakang

Masalah awal: memanggil Gemini memakan 5–50 detik. Kalau dikerjakan langsung di
request web, browser terlihat "membeku" selama itu.

Alurnya sekarang:

1. Controller hanya membuat baris `AiTask` lalu mengirim job ke antrean `ai`
   (`app/Support/AiTaskLauncher.php`) dan langsung mengarahkan pengguna ke halaman progres.
2. Worker menjalankan `app/Jobs/ProcessAiTask.php` (status `processing` → `completed` / `failed`).
   Batas waktunya 170 detik, dan job tidak dicoba ulang otomatis karena pengguna
   punya tombol "Coba lagi".
3. Halaman `resources/views/ai-tasks/show.blade.php` menanyakan status ke
   `route('ai-tasks.status')`:
   - setiap 1,5 detik pada 20 detik pertama, lalu setiap 3 detik;
   - mundur ke 5 detik bila kena rate limit (HTTP 429), dan 4 detik bila jaringan error;
   - otomatis pindah ke hasil saat selesai, atau menampilkan pesan gagal yang ramah.
   Teks petunjuk waktu memakai `aria-live="polite"` sehingga juga dibacakan screen reader.

Tes: `tests/Feature/Quality/AiTaskFlowTest.php`.

### 1.2 Gagal cepat dengan circuit breaker

`app/Support/CircuitBreaker.php` mencatat kegagalan beruntun ke Gemini dan Open-Meteo.
Setelah melewati ambang (diatur di `config/resilience.php`), panggilan berikutnya
langsung ditolak tanpa menunggu timeout. Pengguna segera melihat pesan gangguan,
bukan loading lama yang berakhir error.

### 1.3 Mengurangi kerja database

| Teknik | Lokasi | Efek |
|---|---|---|
| Cache katalog (beranda, jelajah, kategori) selama 10 menit, otomatis dikosongkan saat data katalog berubah | `app/Support/CatalogCache.php` | Halaman publik yang paling sering dibuka tidak meng-query ulang setiap kunjungan |
| Cek favorit satu query per halaman | `app/Support/FavoriteLookup.php` | Menghapus masalah N+1 (sebelumnya satu query per kartu destinasi) |
| Paginasi ulasan | controller & view destinasi | Ukuran halaman tetap kecil walau ulasan bertambah |
| Index komposit | `database/migrations/2026_11_05_000002_add_performance_indexes.php` | Lihat tabel di bawah |

Index yang ditambahkan:

| Tabel | Kolom | Dipakai oleh |
|---|---|---|
| `destinations` | `is_active, category_id` | katalog, beranda, kandidat destinasi AI |
| `reviews` | `destination_id, is_visible, created_at` | rating dan daftar ulasan |
| `itinerary_items` | `itinerary_id, day_number, sort_order` | detail itinerary per hari |
| `itineraries` | `start_date, reminder_sent_at` | pengingat H-1 |

Bila Redis mati, `CatalogCache` tetap mengambil data langsung dari database,
sehingga gangguan cache tidak membuat halaman error.

### 1.4 Pengiriman aset

`docker/nginx/app.conf`:

- kompresi gzip (level 5, file ≥ 1 KB) untuk HTML, CSS, JS, JSON, SVG, dan manifest;
- aset hasil build Vite (`/build/`) di-cache 1 tahun dengan `immutable`, karena nama
  filenya mengandung hash dan berubah setiap rilis;
- gambar dan font di-cache 7 hari.

---

## 2. Usability

**Tujuan:** pengguna bisa menyelesaikan tugasnya tanpa bingung, tanpa kehilangan
data, dan tetap bisa memakai keyboard atau screen reader.

Semua perilaku bersama ada di `resources/js/ux.js`, dengan markup di
`resources/views/partials/ux.blade.php` yang dimuat oleh semua layout.

| Fitur | Cara pakai di view | Perilaku |
|---|---|---|
| Dialog konfirmasi | `<form data-confirm="pesan">` (opsional `data-confirm-title`, `data-confirm-button`) | Memakai `<dialog>` native: fokus terkunci di dialog, Esc atau klik di luar = batal, fokus kembali ke tombol asal. Pesan diisi lewat `textContent`, jadi tidak bisa disisipi script. Kalau `<dialog>` tidak didukung, memakai `window.confirm`. |
| Kunci tombol submit | otomatis untuk semua form POST; kecualikan dengan `data-no-submit-lock` | Tombol dinonaktifkan setelah diklik supaya tidak terkirim dua kali. Dibuka lagi setelah 10 detik (untuk respons unduhan) atau saat kembali lewat tombol Back. |
| Peringatan belum disimpan | `<form data-warn-unsaved>` | Browser memperingatkan saat meninggalkan halaman yang isiannya sudah diubah. Dipakai di form itinerary (buat, ubah, AI) dan form destinasi admin. |
| Field error | otomatis lewat `<x-text-input>` / `<x-password-input>` + `<x-input-error>` | Field yang gagal validasi diberi `aria-invalid="true"` dan border merah, dihubungkan ke pesannya lewat `aria-describedby`, lalu difokuskan dan di-scroll ke tengah (tanpa animasi bila pengguna memilih *reduced motion*). Nama array seperti `items[0][notes]` dicocokkan ke kunci error `items.0.notes`. |
| Banner offline | otomatis | Muncul saat perangkat kehilangan koneksi (`role="status"`), hilang saat online lagi. |

Pendukung lain:

- **Bahasa Indonesia penuh:** pesan validasi, autentikasi, reset password, dan paginasi ada di `lang/id/`.
- **Halaman error yang ramah:** `resources/views/errors/` (401, 403, 404, 419, 429, 5xx, 503).
  Halaman 503 memberi tahu bahwa gangguannya sementara dan memuat ulang sendiri.
- **PWA dan tampilan HP:** `public/manifest.json` dan `public/sw.js`; tata letak memakai
  breakpoint Tailwind (contoh: tombol dialog bertumpuk di HP, sejajar di layar lebar).

Tes: `tests/Feature/Quality/UsabilityEnhancementsTest.php`, antara lain memastikan
tidak ada lagi `return confirm(` di view, pesan konfirmasi berisi tanda kutip tidak
bisa menyisipkan HTML, dan banner offline tersembunyi secara default.

---

## 3. Cara mendemokan

| Yang ditunjukkan | Langkah |
|---|---|
| AI tidak membekukan halaman | Buat itinerary dengan AI → halaman progres langsung tampil dan berpindah sendiri ke hasil |
| Dialog konfirmasi | Hapus item itinerary → dialog muncul; tekan Esc → batal dan fokus kembali ke tombol |
| Kunci tombol | Klik "Simpan" berkali-kali dengan cepat → hanya satu data tersimpan |
| Field error | Kirim form itinerary dengan judul kosong → kursor langsung ke field judul |
| Peringatan belum disimpan | Ketik di form itinerary, lalu tutup tab → muncul peringatan browser |
| Banner offline | DevTools → Network → Offline → banner muncul di bawah layar |
| Kompresi & cache aset | DevTools → Network → buka file `/build/*.js` → header `Content-Encoding: gzip` dan `Cache-Control: ... immutable` |

Menjalankan tes kedua atribut:

```bash
php artisan test tests/Feature/Quality/UsabilityEnhancementsTest.php tests/Feature/Quality/AiTaskFlowTest.php
```

---

## 4. Keterbatasan dan langkah berikutnya

| Keterbatasan | Usulan |
|---|---|
| Waktu proses AI tetap 5–50 detik, bergantung pada Gemini | Tampilkan hasil sebagian (streaming) atau tawarkan templat itinerary siap pakai sambil menunggu |
| Belum ada angka waktu respons sebelum/sesudah cache dan index | Ukur halaman beranda, jelajah, dan detail itinerary (misalnya dengan Laravel Debugbar atau `ab`/`k6`) lalu catat di dokumen ini |
| Belum ada audit Lighthouse | Jalankan Lighthouse (Performance, Accessibility, PWA) di halaman utama dan perbaiki temuannya |
| Belum diuji dengan pengguna nyata | Uji 5 orang dengan tugas: daftar + aktifkan 2FA, buat itinerary dengan AI, tambah ulasan; catat waktu dan kendala |
| Peringatan "belum disimpan" baru ada di form panjang | Tambahkan `data-warn-unsaved` ke form lain yang isiannya panjang (misalnya ulasan) bila diperlukan |
