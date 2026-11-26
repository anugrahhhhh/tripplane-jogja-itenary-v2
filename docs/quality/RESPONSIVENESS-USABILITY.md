# Responsiveness & Usability — JogjaTrip

Bagian Eunice dari [ANALISIS-KUALITAS.md](../ANALISIS-KUALITAS.md).

## 6. Responsiveness

**Definisi:** seberapa cepat sistem merespons aksi pengguna.

| Implementasi | Bukti di sistem |
|---|---|
| Permintaan AI diproses di latar belakang (queue) dengan halaman progres | `app/Jobs/ProcessAiTask.php`, `resources/views/ai-tasks/show.blade.php` |
| Circuit breaker → kegagalan langsung terlihat, tanpa menunggu timeout | `app/Support/CircuitBreaker.php` |
| Cache katalog, perbaikan query N+1 favorit, paginasi ulasan | `app/Support/CatalogCache.php`, `FavoriteLookup.php` |
| Index database untuk query yang sering dipakai | `database/migrations/2026_11_05_000002_add_performance_indexes.php` |
| Kompresi gzip dan cache permanen aset CSS/JS | `docker/nginx/app.conf` |

**Keterbatasan:** waktu proses AI tetap 5–50 detik tergantung layanan Gemini.

## 7. Usability

**Definisi:** kemudahan dan kenyamanan sistem dipakai oleh pengguna.

| Implementasi | Bukti di sistem |
|---|---|
| Seluruh pesan dalam Bahasa Indonesia | `lang/id/` |
| Dialog konfirmasi yang aksesibel (keyboard & screen reader) | `resources/views/partials/ux.blade.php` |
| Tombol terkunci setelah diklik (mencegah data dobel) | `resources/js/ux.js` |
| Field error ditandai (`aria-invalid`) dan langsung difokuskan | `components/text-input.blade.php` |
| Peringatan saat meninggalkan form yang belum disimpan, banner offline | `resources/js/ux.js` |
| Halaman error/gangguan yang ramah dan memuat ulang sendiri | `resources/views/errors/` |
| Responsif di HP, bisa dipasang sebagai PWA | `public/sw.js`, `manifest.json` |

**Keterbatasan:** belum diuji dengan pengguna nyata atau audit Lighthouse.
