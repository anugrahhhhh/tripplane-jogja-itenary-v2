# Analisis Atribut Kualitas Produk — JogjaTrip

Dokumen ini memetakan tujuh atribut kualitas produk perangkat lunak (Security, Reliability, Availability, Resilience, Maintainability, Responsiveness, Usability) ke implementasi nyata di sistem JogjaTrip, beserta bukti dan keterbatasannya.

| Atribut | Status | Penanggung jawab presentasi |
|---|---|---|
| Security | Kuat | Jose |
| Availability | Kuat | Anugrah |
| Resilience | Sangat kuat | Anugrah |
| Reliability | Kuat | Venessa |
| Maintainability | Kuat | Venessa |
| Responsiveness | Kuat | Eunice |
| Usability | Kuat | Eunice |

Demo aplikasi (alur login 2FA, susun itinerary dengan AI, dashboard admin) dibawakan oleh Jose.

Setiap bagian di bawah mencantumkan **commit terkait** yang menjadi bahan penjelasan atribut tersebut (lihat dengan `git show <hash>`). Dua commit besar mencakup beberapa atribut sekaligus, sehingga muncul di lebih dari satu bagian:

- `02c69eb` *harden quality attributes* → Security, Reliability, Maintainability, Responsiveness, Usability
- `2d20709` *strengthen availability, resilience, responsiveness and usability* → Availability, Resilience, Responsiveness, Usability

---

## 1. Security

**Definisi:** kemampuan sistem melindungi data dan akun dari akses yang tidak sah.

| Implementasi | Bukti di sistem |
|---|---|
| Login dua langkah (TOTP) wajib, recovery code, perangkat tepercaya | `app/Services/Auth/TwoFactorAuthenticator.php`, `TrustedDeviceManager.php` |
| Notifikasi login baru + tombol "Bukan saya" untuk mengamankan akun | `app/Actions/Auth/SecureAccountAction.php` |
| Header keamanan (CSP, HSTS, X-Frame-Options, Permissions-Policy) | `app/Http/Middleware/SecurityHeaders.php` |
| Pembatasan percobaan (rate limit) login, daftar, reset password, AI | `app/Providers/AppServiceProvider.php` |
| Link email selalu dari `APP_URL` (mencegah *password reset poisoning*) | `app/Support/OutboundUrl.php` |
| Auto-logout saat idle, halaman sensitif tidak di-cache | `LogoutIdleSessions.php`, `PreventSensitiveCaching.php` |
| Dialog konfirmasi aman dari injeksi JavaScript | `resources/js/ux.js` (atribut `data-confirm`) |

**Commit terkait (Jose):**
- 2FA: `ea0aaf2` kolom TOTP & service, `7b89584` perangkat tepercaya, `c564549` setup TOTP wajib, `5131127` TwoFactorGate & challenge, `0eb1614` login admin/Google lewat 2FA
- Notifikasi & "Bukan saya": `4541b68`, `6f921ad`, `d7c7350`, `b687060`
- Pengamanan token & recovery code: `8a1d85c`, `ffee2e0`; reset 2FA oleh admin: `9b4e4c0`, `46b95f8`
- Auto-logout idle: `fc6c7af`
- Password & kunci aplikasi: `74e971d` rehash password, `9c7266c` tolak APP_KEY otomatis di production
- Cache halaman sensitif: `d9f514b`, `177024e`, `9b65217`
- Library rawan: `0b8558d`
- `02c69eb` bagian Security (OutboundUrl, SecurityHeaders, rate limit daftar/reset password)
- README: `0a0e3d3`

**Keterbatasan:** CSP untuk `script-src` masih longgar karena masih ada script inline di view.

## 2. Availability

**Definisi:** sistem tetap bisa diakses pengguna ketika dibutuhkan.

| Implementasi | Bukti di sistem |
|---|---|
| Dua instance aplikasi (app1, app2) di belakang Nginx; satu mati → dialihkan otomatis | `docker-compose.yml`, `docker/nginx/app.conf` (`upstream`, `fastcgi_next_upstream`) |
| Halaman cadangan statis bila semua instance mati (bukan "502 Bad Gateway") | `docker/nginx/fallback.html` |
| Replika database MySQL (GTID) untuk pembacaan | `docker/mysql/setup-replica.sh` |
| Health check `/health`, perintah `app:health`, kartu Kesehatan Sistem di dashboard admin | `app/Services/SystemHealth.php`, `HealthController.php` |
| Peringatan otomatis (email + notifikasi admin) saat sistem bermasalah/pulih | `app/Services/HealthAlerter.php` |
| Backup harian terverifikasi ke 2 lokasi + perintah restore | `app/Services/Backup/BackupManager.php`, `app:backup-database`, `app:restore-database` |

**Uji nyata (chaos test):** app1 dimatikan → web tetap 200; app1 + app2 dimatikan → halaman cadangan; Redis + replika dimatikan → web tetap 200 (~1 detik); semua dinyalakan → pulih sendiri.

**Commit terkait (Anugrah):**
- Fondasi & Docker: `0da864b` first commit, `d857657` Docker (app, nginx, MySQL, scheduler), `65bc548` queue worker & syarat deploy
- Deploy cloud: `6f28fd8` PaaS Railway + panduan IaaS/PaaS, `73c970f` script VM IaaS, `76a6fe9` laporan & slide cloud computing
- `2d20709` bagian Availability (2 instance + failover Nginx, fallback.html, replika MySQL, `/health`, HealthAlerter, backup & restore)
- Integrasi: `15bfc4e` merge PR #1 (2FA), `3ea7daa` merge PR #2 (paket 1), `b05699d` merge branch availability

**Keterbatasan:** semua container berjalan di satu mesin; bila database primary rusak total, pemulihan masih manual (restore dari backup).

## 3. Resilience

**Definisi:** kemampuan sistem tetap berfungsi atau gagal dengan anggun saat ada gangguan.

| Implementasi | Bukti di sistem |
|---|---|
| Circuit breaker untuk Gemini AI dan Open-Meteo (cuaca) | `app/Support/CircuitBreaker.php` |
| Model AI cadangan + batas waktu total | `app/Services/Concerns/CallsGeminiApi.php` |
| Cuaca memakai data terakhir yang berhasil saat API gangguan (*stale-if-error*) | `app/Services/WeatherService.php` |
| Cache Redis otomatis pindah ke database bila Redis mati | `config/cache.php` (store `failover`) |
| Database putus → halaman 503 "gangguan sementara" + `Retry-After` | `app/Support/DatabaseOutage.php` |
| Transaksi diulang saat deadlock; email dicoba ulang 5x dengan jeda bertahap | `app/Support/Transaction.php`, `app/Notifications/Concerns/RetriesDelivery.php` |
| Pengingat H-1 terisolasi per itinerary dan dicoba ulang tiap jam | `app/Console/Commands/SendItineraryReminders.php` |

**Commit terkait (Anugrah):**
- `2d20709` bagian Resilience (CircuitBreaker, cuaca *stale-if-error*, failover Redis, DatabaseOutage 503, retry deadlock & email, pengingat H-1)
- `02c69eb` batas waktu total + model cadangan Gemini (`CallsGeminiApi`)

## 4. Reliability

**Definisi:** sistem bekerja benar dan konsisten sesuai yang diharapkan.

| Implementasi | Bukti di sistem |
|---|---|
| Transaksi database untuk operasi berlapis (urutkan itinerary, simpan hasil AI) | `ItineraryItemController.php`, `app/Jobs/ProcessAiTask.php` |
| Validasi jadwal: jam buka dan hari tutup destinasi | `app/Services/ScheduleValidator.php`, `OperatingHoursService.php` |
| Konsistensi *read-your-writes* saat memakai replika database | `app/Http/Middleware/ReadYourWrites.php` |
| Backup diverifikasi (gzip utuh, dump lengkap, checksum SHA-256) | `BackupManager::verify()` |
| 418 automated test + CI GitHub Actions di setiap push | `tests/`, `.github/workflows/tests.yml` |
| Pengaman agar test tidak menghapus database development | `tests/TestCase.php` |

**Commit terkait (Venessa):**
- Validasi hari tutup destinasi: `4138386`, `4863239`
- `02c69eb` bagian Reliability (transaksi DB untuk urutkan itinerary & "Itinerary Hari Ini", 80+ test baru)
- `eb70cb7` test menolak berjalan di database non-test

**Keterbatasan:** grafik dashboard admin memakai fungsi khusus MySQL sehingga tidak ikut diuji di SQLite.

## 5. Maintainability

**Definisi:** kemudahan sistem dipahami, diubah, dan dikembangkan.

| Implementasi | Bukti di sistem |
|---|---|
| Pemisahan lapisan: Controller tipis, logika di `Services`, utilitas di `Support`, proses latar di `Jobs` | struktur folder `app/` |
| Konfigurasi terpusat lewat `.env` | `config/resilience.php`, `config/backup.php` |
| Gaya kode seragam (Laravel Pint) dicek otomatis di CI | `.github/workflows/tests.yml` |
| Dokumentasi UML (PlantUML) dan panduan operasional | `docs/uml-plantuml/`, `docs/deployment/AVAILABILITY.md` |

**Commit terkait (Venessa):**
- `02c69eb` bagian Maintainability (CI GitHub Actions + Pint, hapus file tak terpakai)
- Design spec & rencana implementasi: `366eeb7`, `b1261a6`, `815ddc6`, `0923313`
- Diagram UML: `a92b143`, `ba93798`
- Dokumentasi: `4623737` analisis kualitas, `e07d69a` & `05ec017` pembagian tugas, `30ba41a` README

**Keterbatasan:** belum ada analisis statis (PHPStan) dan laporan *coverage*; infrastruktur Docker kini lebih banyak komponen yang harus dirawat.

## 6. Responsiveness

**Definisi:** seberapa cepat sistem merespons aksi pengguna.

| Implementasi | Bukti di sistem |
|---|---|
| Permintaan AI diproses di latar belakang (queue) dengan halaman progres | `app/Jobs/ProcessAiTask.php`, `resources/views/ai-tasks/show.blade.php` |
| Circuit breaker → kegagalan langsung terlihat, tanpa menunggu timeout | `app/Support/CircuitBreaker.php` |
| Cache katalog, perbaikan query N+1 favorit, paginasi ulasan | `app/Support/CatalogCache.php`, `FavoriteLookup.php` |
| Index database untuk query yang sering dipakai | `database/migrations/2026_11_05_000002_add_performance_indexes.php` |
| Kompresi gzip dan cache permanen aset CSS/JS | `docker/nginx/app.conf` |

**Commit terkait (Eunice):**
- `2d20709` bagian Responsiveness (antrean AI + halaman progres, index database, gzip & cache aset)
- `02c69eb` bagian Performance (CatalogCache, perbaikan N+1 favorit, paginasi ulasan, lazy-load gambar)
- Dokumentasi: `47c7d66`, `24dadb5` penjelasan Responsiveness & Usability

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

**Commit terkait (Eunice):**
- `2d20709` bagian Usability (dialog konfirmasi aksesibel, submit lock, `aria-invalid`, banner offline)
- `02c69eb` bagian Usability (terjemahan Bahasa Indonesia, halaman error ramah, label form)
- Peringatan idle yang jelas: `0076095`, `2eb9c3e`, `5ca0f25`, `46537cf`, `21d4927`
- Profil keamanan & banner recovery code: `0839947`
- Fitur itinerary: `2809079` & `b71fc58` estimasi biaya tiket, `d588191` & `b172cb0` link share
- README: `7e9fd6a`

**Keterbatasan:** belum diuji dengan pengguna nyata atau audit Lighthouse.

---

## Trade-off antar atribut

- **Security ↔ Usability:** 2FA dan auto-logout menambah langkah bagi pengguna; fitur *perangkat tepercaya* menyeimbangkan keduanya.
- **Availability/Resilience ↔ Maintainability & biaya:** dua instance aplikasi dan replika database meningkatkan ketersediaan, tetapi menambah komponen yang harus dirawat dan biaya server.
- **Replika baca ↔ konsistensi:** replika bisa sedikit tertinggal; diatasi dengan *read-your-writes*, dengan konsekuensi sebagian pembacaan tetap ke primary.
- **Responsiveness ↔ kompleksitas:** AI di latar belakang membuat aplikasi terasa cepat, tetapi butuh worker antrean dan deteksi permintaan macet.
