# Desain: Paket 1 — Cache Offline Aman, Update Library, Estimasi Biaya, Hari Tutup, Link Berbagi Itinerary

- **Tanggal:** 2026-10-30
- **Status:** Menunggu review
- **Branch:** `feature/paket-1-perbaikan-fitur` (dibuat dari `feature/two-factor-auth`, PR #1 belum di-merge)
- **Target:** Production

## 1. Tujuan

Lima perubahan yang saling lepas, dikerjakan dalam satu paket:

| Kode | Perubahan | Alasan |
|---|---|---|
| A1 | Halaman pribadi yang tersimpan di cache service worker dihapus saat logout, dan halaman sensitif tidak pernah disimpan | Setelah logout, halaman itinerary/profil (bahkan recovery code 2FA) masih bisa dibuka dari cache di komputer bersama |
| A2 | Update `league/commonmark` dan `symfony/mime` | `composer audit` melaporkan celah (high dan medium) |
| B1 | Estimasi biaya tiket masuk per hari dan total, per orang dan untuk seluruh peserta | Data harga sudah ada di destinasi tapi belum dimanfaatkan |
| B3 | Data hari tutup destinasi dan peringatan di itinerary | Banyak destinasi (mis. museum) tutup di hari tertentu |
| B4 | Link publik hanya-baca untuk berbagi itinerary | Sekarang berbagi hanya lewat undangan kolaborator yang wajib login |

### Keputusan yang sudah disepakati

| Topik | Keputusan |
|---|---|
| Offline vs keamanan | Itinerary tetap bisa dibuka offline selama login; cache halaman dihapus saat logout. |
| Biaya | Per orang + jumlah peserta (kolom baru `traveler_count`, default 1). |
| Hari tutup | Diisi admin lewat form (checkbox Senin–Minggu). Data awal kosong = buka setiap hari. Tidak ada data yang dikarang di seeder. |
| Link berbagi | Hanya pemilik yang mengelola; halaman publik hanya-baca tanpa data pribadi. |

### Di luar cakupan

Biaya transportasi/makan/parkir, prakiraan cuaca di halaman publik, pengisian otomatis hari tutup, membagikan ke media sosial dengan preview khusus, perubahan pada prompt AI planner.

## 2. A1 — Cache offline yang aman

### 2.1 Penghapusan cache saat logout

- Setiap respons yang mengakhiri sesi mengirim header `Clear-Site-Data: "cache", "storage"` (`"cache"` hanya membuang cache HTTP; `"storage"` membuang Cache Storage tempat service worker menyimpan halaman):
  - `AuthenticatedSessionController@destroy` (logout manual dan logout dari form idle `reason=idle`);
  - `LogoutIdleSessions` saat sesi kedaluwarsa (respons redirect maupun JSON 401);
  - `NotMeController@confirm` (POST "Bukan saya");
  - `ProfileController@destroy` (hapus akun).
- Header dipasang lewat satu helper agar tidak terduplikasi: `App\Support\ClearSiteCache::apply(Symfony\Component\HttpFoundation\Response $response): Response`.
- Pengaman untuk browser yang tidak mendukung `Clear-Site-Data`: halaman login wisatawan dan admin memuat skrip kecil yang menghapus Cache Storage bernama `jogjatrip-*-pages` (halaman login hanya untuk tamu, jadi tidak ada halaman pribadi yang perlu dipertahankan). Aset (`*-assets`) tidak dihapus.
- Fallback flash: setiap redirect yang memakai `ClearSiteCache::apply` juga mengisi flash `clear_page_cache`; layout `app` dan `guest` memuat skrip pembersih yang sama bila flash ada, dan halaman "Bukan saya" selesai selalu memuatnya.

### 2.2 Halaman yang tidak boleh disimpan

- Middleware baru `App\Http\Middleware\PreventSensitiveCaching` menambahkan `Cache-Control: no-store, private` pada route bernama:
  `two-factor.*`, `profile.*`, `security.not-me*`, `notifications.*`, `admin.*`, `password.*`, `login`, `register`, `verification.*`.
- Middleware didaftarkan di grup `web` (mengecek `routeIs`).

### 2.3 Service worker (`public/sw.js`)

- `CACHE_VERSION` dinaikkan ke `jogjatrip-v2` → cache v1 (yang mungkin berisi halaman pribadi) dihapus saat aktivasi oleh logika yang sudah ada.
- `networkFirstForPages` hanya menyimpan respons jika: `response.ok` (status 200), `!response.redirected`, `response.type === 'basic'`, dan header `Cache-Control` tidak mengandung `no-store`.
- Perilaku lain tidak berubah (network-first untuk halaman, cache-first untuk aset, fallback `/offline`).

## 3. A2 — Update library

- `composer update league/commonmark symfony/mime --with-dependencies` (hanya dua package itu beserta dependensinya).
- Kriteria: `composer audit` tidak melaporkan advisory untuk kedua package, dan seluruh test lulus.
- Jika versi yang aman membutuhkan upgrade major yang memecah kompatibilitas, hentikan dan laporkan (jangan paksa).

## 4. B1 — Estimasi biaya

### 4.1 Data

Migrasi: `itineraries.traveler_count` `unsignedTinyInteger`, default 1. Model: `$fillable` + cast `integer`.

### 4.2 Form

- Form buat (`itineraries.create`) dan edit (`itineraries.edit`): input angka "Jumlah peserta", `required|integer|min:1|max:50`, default 1.
- Itinerary dari AI (`AiItineraryController`) dan "Itinerary hari ini" (`TodayItineraryController`) memakai default 1 (bisa diubah lewat edit).

### 4.3 Perhitungan — `App\Services\ItineraryCostEstimator`

```php
public static function estimate(Itinerary $itinerary): array
// return [
//   'days' => [day_number => ['min' => int, 'max' => int]],   // per orang
//   'total' => ['min' => int, 'max' => int],                  // per orang
//   'travelers' => int,
//   'group_total' => ['min' => int, 'max' => int],            // total * travelers
// ]
```

- Setiap item dihitung sekali (destinasi yang sama dikunjungi dua kali dihitung dua kali).
- Hari tanpa item tidak muncul di `days`.
- Memakai relasi `items.destination` yang sudah di-load (tanpa query tambahan per item).

### 4.4 Tampilan

- Halaman itinerary: kartu "Estimasi biaya" (total) dan baris kecil per hari.
- Format: `Rp 50.000 – Rp 120.000 per orang · Rp 200.000 – Rp 480.000 untuk 4 orang`. Jika min = max → satu angka. Jika 0 → "Gratis". Jika `travelers` = 1 → bagian "untuk N orang" tidak ditampilkan.
- Catatan kecil: "Estimasi tiket masuk saja, belum termasuk transportasi, makan, dan parkir."
- Format rupiah lewat satu helper `App\Support\Rupiah::format(int $amount): string` → `Rp 50.000` (pemisah ribuan titik).
- Ditampilkan juga di PDF dan di halaman publik (B4).

## 5. B3 — Hari tutup

### 5.1 Data

Migrasi: `destinations.closed_days` `json` nullable. Model: cast `array`; isi = daftar hari ISO 8601 (`1`=Senin … `7`=Minggu), unik, terurut. `null`/`[]` = buka setiap hari.

### 5.2 Admin

- Form destinasi: grup checkbox "Hari tutup" Senin–Minggu.
- Validasi: `closed_days` `nullable|array`, `closed_days.*` `integer|between:1,7|distinct`. Disimpan terurut; jika tidak ada yang dicentang → `null`.

### 5.3 Tampilan destinasi

Halaman detail menampilkan "Tutup: Senin" / "Tutup: Senin, Selasa" di dekat jam operasional bila ada. Nama hari dari helper `App\Support\Weekday::name(int $iso): string` (`Senin`, `Selasa`, `Rabu`, `Kamis`, `Jumat`, `Sabtu`, `Minggu`).

### 5.4 Peringatan itinerary

- `ScheduleValidator::validateDay(Collection $dayItems, ?CarbonInterface $date = null)` — parameter baru opsional (pemanggil lama tetap berfungsi).
- Jika `$date` ada dan `isoWeekday` termasuk `closed_days` destinasi → peringatan `"Tutup setiap hari {Hari}."`.
- `ItineraryController::buildDayInsights` mengirim `$date = start_date + (day_number - 1) hari`.
- Peringatan muncul di halaman itinerary, PDF, dan halaman publik (memakai mekanisme `warningsByDay` yang sudah ada).

## 6. B4 — Link berbagi itinerary

### 6.1 Data

Migrasi: `itineraries.share_token` `string(64)` nullable unique. Tidak masuk `$fillable` (diisi lewat `forceFill`).

### 6.2 Kelola link (pemilik saja)

- `POST /itinerary/{itinerary}/bagikan` → `itineraries.share.store`: buat token baru `Str::random(40)` (juga dipakai untuk "Buat link baru"; token lama otomatis tidak berlaku).
- `DELETE /itinerary/{itinerary}/bagikan` → `itineraries.share.destroy`: `share_token = null`.
- Non-pemilik (termasuk kolaborator) → 403.
- Halaman itinerary (pemilik): kartu "Bagikan itinerary". Belum aktif → tombol "Buat link berbagi". Aktif → field link + tombol "Salin" (Clipboard API), "Buat link baru" (dengan `confirm()`), "Matikan link".

### 6.3 Halaman publik

- `GET /bagikan/{token}` → `itineraries.shared` (di luar grup `auth`, middleware `throttle:60,1`). Controller: `App\Http\Controllers\SharedItineraryController@show`.
- Token tidak ditemukan → 404.
- Header `X-Robots-Tag: noindex, nofollow`.
- Isi: judul, rentang tanggal, jumlah peserta, jadwal per hari (jam mulai, nama destinasi tertaut ke halaman destinasi publik, durasi), peta per hari, waktu tempuh, peringatan, estimasi biaya.
- **Tidak ditampilkan:** nama/email pemilik, kolaborator, catatan item (`notes`), tombol edit apa pun.
- Layout: `x-app-layout` (navigasi publik tetap tampil, idle-timeout hanya aktif bila pengunjung sedang login).
- Logika per hari (peta, peringatan, waktu tempuh) memakai ulang `buildDayInsights` yang dipindahkan ke service `App\Services\ItineraryInsights::build(Itinerary $itinerary): array{0: array, 1: array, 2: array}` agar tidak terduplikasi antara `ItineraryController` dan `SharedItineraryController`.

## 7. Error handling

| Situasi | Perilaku |
|---|---|
| Browser tidak mendukung `Clear-Site-Data` | Skrip di halaman login menghapus cache halaman |
| Clipboard API tidak tersedia | Field link bisa diseleksi manual; tombol "Salin" menampilkan teks "Salin manual" |
| `traveler_count` di luar 1–50 | Error validasi Indonesia: "Jumlah peserta harus antara 1 dan 50." |
| Destinasi tanpa harga (0–0) | "Gratis" |
| Token berbagi salah/dimatikan | 404 |
| `composer update` butuh upgrade major | Berhenti dan laporkan |

## 8. Testing

PHPUnit feature/unit test, TDD:

- **A1:** logout manual, logout idle (redirect dan JSON), dan POST "Bukan saya" mengirim `Clear-Site-Data: "cache", "storage"` (termasuk hapus akun); route sensitif mengirim `Cache-Control` berisi `no-store`; halaman publik (katalog) tidak. Halaman login memuat skrip pembersih cache.
- **A2:** `composer audit` bersih untuk kedua package; suite lulus.
- **B1:** unit test estimator (hari kosong, gratis, kunjungan ganda, perkalian peserta, min=max); validasi `traveler_count`; tampilan format rupiah dan "Gratis"; PDF memuat estimasi.
- **B3:** validasi form admin (1–7, distinct, kosong → null); tampilan "Tutup: Senin"; peringatan muncul pada tanggal yang tepat, termasuk itinerary yang melewati pergantian minggu; tanpa `$date` tidak ada peringatan hari tutup.
- **B4:** pemilik bisa membuat/membuat ulang/mematikan; kolaborator dan user lain 403; tamu bisa membuka link; token lama 404 setelah dibuat ulang; respons tidak memuat email pemilik, nama kolaborator, maupun `notes`; header `X-Robots-Tag`.

**Manual (Playwright):** setelah login dan membuka itinerary, Cache Storage memuat halamannya; setelah logout, cache halaman kosong; halaman recovery code tidak pernah masuk cache.
