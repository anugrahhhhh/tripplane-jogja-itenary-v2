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

- `00c1fea` *harden quality attributes* → Security, Reliability, Maintainability, Responsiveness, Usability
- `d6b1897` *strengthen availability, resilience, responsiveness and usability* → Availability, Resilience, Responsiveness, Usability

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
- 2FA: `84ee034` kolom TOTP & service, `447c420` perangkat tepercaya, `d1a8fa3` setup TOTP wajib, `674c2c2` TwoFactorGate & challenge, `eaba215` login admin/Google lewat 2FA
- Notifikasi & "Bukan saya": `0920dd8`, `bd85c56`, `40799c3`, `a2f1b9d`
- Pengamanan token & recovery code: `7abe915`, `0726103`; reset 2FA oleh admin: `491bdd5`, `7fedc5d`
- Auto-logout idle: `18a354a`
- Password & kunci aplikasi: `502298a` rehash password, `319cd38` tolak APP_KEY otomatis di production
- Cache halaman sensitif: `bd50731`, `4aafcda`, `0b7f7d6`
- Library rawan: `607cd95`
- `00c1fea` bagian Security (OutboundUrl, SecurityHeaders, rate limit daftar/reset password)
- README: `2889ef9`

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
- Fondasi & Docker: `0da864b` first commit, `d857657` Docker (app, nginx, MySQL, scheduler), `0c344c7` queue worker & syarat deploy
- Deploy cloud: `accda8d` PaaS Railway + panduan IaaS/PaaS, `23e665f` script VM IaaS, `a60ecca` laporan & slide cloud computing
- `d6b1897` bagian Availability (2 instance + failover Nginx, fallback.html, replika MySQL, `/health`, HealthAlerter, backup & restore)
- Integrasi: `0198dd3` merge PR #1 (2FA), `18f338d` merge PR #2 (paket 1), `9d67302` merge branch availability

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
- `d6b1897` bagian Resilience (CircuitBreaker, cuaca *stale-if-error*, failover Redis, DatabaseOutage 503, retry deadlock & email, pengingat H-1)
- `00c1fea` batas waktu total + model cadangan Gemini (`CallsGeminiApi`)

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
- Validasi hari tutup destinasi: `5f68bd5`, `773d302`
- `00c1fea` bagian Reliability (transaksi DB untuk urutkan itinerary & "Itinerary Hari Ini", 80+ test baru)
- `02da314` test menolak berjalan di database non-test

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
- `00c1fea` bagian Maintainability (CI GitHub Actions + Pint, hapus file tak terpakai)
- Design spec & rencana implementasi: `4860d87`, `cefda2e`, `ac5b9f1`, `c35c3d4`
- Diagram UML: `21c7d8d`, `64fb0f5`
- Dokumentasi: `4c0f684` analisis kualitas, `8bf376d` & `7aad985` pembagian tugas, `a4fcbbb` README

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
- `d6b1897` bagian Responsiveness (antrean AI + halaman progres, index database, gzip & cache aset)
- `00c1fea` bagian Performance (CatalogCache, perbaikan N+1 favorit, paginasi ulasan, lazy-load gambar)
- Dokumentasi: `8336e5e`, `578e694` penjelasan Responsiveness & Usability

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
- `d6b1897` bagian Usability (dialog konfirmasi aksesibel, submit lock, `aria-invalid`, banner offline)
- `00c1fea` bagian Usability (terjemahan Bahasa Indonesia, halaman error ramah, label form)
- Peringatan idle yang jelas: `4910d16`, `a5a1672`, `80843f9`, `11c531a`, `f63ce2a`
- Profil keamanan & banner recovery code: `244ca5e`
- Fitur itinerary: `89dffc3` & `4ba6d0a` estimasi biaya tiket, `8df1a56` & `d8aabdd` link share
- README: `42fbb8a`

**Keterbatasan:** belum diuji dengan pengguna nyata atau audit Lighthouse.

---

## Trade-off antar atribut

- **Security ↔ Usability:** 2FA dan auto-logout menambah langkah bagi pengguna; fitur *perangkat tepercaya* menyeimbangkan keduanya.
- **Availability/Resilience ↔ Maintainability & biaya:** dua instance aplikasi dan replika database meningkatkan ketersediaan, tetapi menambah komponen yang harus dirawat dan biaya server.
- **Replika baca ↔ konsistensi:** replika bisa sedikit tertinggal; diatasi dengan *read-your-writes*, dengan konsekuensi sebagian pembacaan tetap ke primary.
- **Responsiveness ↔ kompleksitas:** AI di latar belakang membuat aplikasi terasa cepat, tetapi butuh worker antrean dan deteksi permintaan macet.
