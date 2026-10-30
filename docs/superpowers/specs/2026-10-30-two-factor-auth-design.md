# Desain: 2FA (TOTP), Notifikasi Login Baru, dan Auto-Logout

- **Tanggal:** 2026-10-30
- **Status:** Menunggu review
- **Target:** Production (aplikasi akan di-deploy untuk user sungguhan)

## 1. Tujuan

Melindungi akun JogjaTrip dari pengambilalihan, dan memberi tahu pemilik akun saat ada yang masuk.

1. **2FA wajib untuk semua akun** (wisatawan, admin, termasuk yang masuk lewat Google) memakai aplikasi authenticator (TOTP).
2. **Notifikasi login baru** (in-app + email) saat login dari perangkat yang belum tepercaya, dengan tombol **"Bukan saya"** untuk mengamankan akun.
3. **Auto-logout** setelah 5 menit tidak aktif, dengan peringatan 60 detik sebelumnya.

### Keputusan yang sudah disepakati

| Topik | Keputusan |
|---|---|
| Metode 2FA | TOTP (Google Authenticator dsb.) + 8 recovery code. Tidak memakai OTP email/push approval. |
| Cakupan | Wajib untuk semua user, termasuk login Google. |
| Perangkat tepercaya | Opsi "Percayai perangkat ini selama 30 hari"; bisa dilihat dan dicabut di Profil. |
| Auto-logout | 5 menit tidak aktif, modal peringatan di menit ke-4. |
| Implementasi | `pragmarx/google2fa` (hitungan TOTP) + `bacon/bacon-qr-code` (QR); alur, controller, dan view ditulis sendiri mengikuti pola Breeze. Tidak memakai Fortify. |

### Di luar cakupan

Passkey/WebAuthn, push notification, OTP via email/SMS, lokasi GeoIP, halaman riwayat login.

## 2. Alur Login

### 2.1 Pintu masuk

Ada tiga pintu masuk, dan semuanya menyerahkan user ke `TwoFactorGate` setelah kredensial utama valid:

- `AuthenticatedSessionController@store` (`/login`, wisatawan)
- `Admin\AdminAuthenticatedSessionController@store` (`/admin/login`)
- `GoogleAuthController@callback`

Pengecekan peran yang sudah ada (admin ditolak di `/login` dan di Google; pemisahan pintu masuk) tetap dijalankan **sebelum** gerbang.

### 2.2 `TwoFactorGate` (`app/Services/Auth/TwoFactorGate.php`)

Input: `User`, `Request`, metode login (`password` | `google`), tujuan redirect. Gerbang memilih satu dari tiga cabang:

1. **Belum setup** (`two_factor_confirmed_at` null): `Auth::login($user)` tanpa remember, lalu redirect ke `two-factor.setup`. Middleware `EnsureTwoFactorSetUp` membatasi akses.
2. **Sudah setup + cookie perangkat tepercaya valid** untuk user ini: `Auth::login($user)`, perbarui `last_used_at` perangkat, lalu redirect ke tujuan. Tidak ada notifikasi.
3. **Sudah setup + perangkat belum tepercaya**: **tidak** login. Session menyimpan `two_factor.pending = {user_id, method, intended, expires_at = now+5 menit}`, lalu redirect ke `two-factor.challenge`.

### 2.3 Halaman challenge (`/dua-faktor`)

- Hanya bisa diakses jika ada `two_factor.pending` yang belum kedaluwarsa. Jika tidak ada atau sudah kedaluwarsa, user diarahkan ke halaman login yang sesuai dengan pesan "Waktu habis, silakan masuk lagi".
- Input: kode TOTP 6 digit **atau** recovery code (toggle "Pakai recovery code"), plus checkbox "Percayai perangkat ini selama 30 hari".
- Jika berhasil: hapus `two_factor.pending`, `Auth::login($user)`, `session()->regenerate()`, buat perangkat tepercaya jika dicentang, kirim `NewLoginNotification`, lalu redirect ke tujuan.
- Rate limit: 5 percobaan per menit per `user_id` + IP (`RateLimiter`), dengan pesan hitung mundur.

### 2.4 Halaman setup (`/dua-faktor/aktifkan`)

1. Secret baru dibuat dan disimpan di **session** (belum ke DB). QR code (SVG inline) dan secret teks ditampilkan untuk input manual.
2. User memasukkan kode dari aplikasi. Jika valid: simpan `two_factor_secret`, `two_factor_confirmed_at`, `two_factor_last_used_step`, lalu buat 8 recovery code.
3. Halaman recovery code ditampilkan **sekali** (tombol salin/unduh .txt), dengan konfirmasi "Saya sudah menyimpannya" sebelum lanjut ke tujuan.
4. Kirim notifikasi `TwoFactorEnabledNotification` (email + in-app).

### 2.5 Middleware `EnsureTwoFactorSetUp`

Dipasang di semua grup route ber-`auth` (wisatawan dan admin). Jika user login tetapi `two_factor_confirmed_at` null, semua request diarahkan ke `two-factor.setup`, **kecuali** route setup itu sendiri, logout, dan route ping/perpanjang sesi.

### 2.6 Penghapusan remember-me

- Checkbox "Ingat saya" dihapus dari view login wisatawan dan admin; `LoginRequest` tidak lagi meneruskan `remember`.
- `GoogleAuthController` memakai `Auth::login($user)` (tanpa `true`).
- Alasan: cookie remember-me akan me-login-kan user lagi diam-diam setelah auto-logout dan melewati 2FA.

### 2.7 Aturan verifikasi kode

- TOTP 6 digit, periode 30 detik, toleransi ±1 slot.
- **Anti-replay:** kode diterima hanya jika slot waktunya > `two_factor_last_used_step`; setelah berhasil, kolom itu diperbarui.
- **Recovery code:** format `xxxxx-xxxxx`, dicocokkan terhadap daftar hash; jika cocok, hash itu dihapus (sekali pakai). Login dengan recovery code memicu `RecoveryCodeUsedNotification` (berisi sisa jumlah kode).

## 3. Struktur Data

### 3.1 Migrasi: kolom baru di `users`

| Kolom | Tipe | Catatan |
|---|---|---|
| `two_factor_secret` | `text`, nullable | Cast `encrypted`. |
| `two_factor_recovery_codes` | `text`, nullable | Cast `encrypted:array`; berisi hash `Hash::make` per kode. |
| `two_factor_confirmed_at` | `timestamp`, nullable | Null = wajib setup. |
| `two_factor_last_used_step` | `unsignedBigInteger`, nullable | Anti-replay. |

Keempat kolom masuk `$hidden` dan **tidak** masuk `$fillable` (diisi lewat `forceFill`).

### 3.2 Tabel baru `trusted_devices`

| Kolom | Tipe |
|---|---|
| `id` | bigint PK |
| `user_id` | FK → `users`, `cascadeOnDelete` |
| `token_hash` | `char(64)`, unique (SHA-256 dari token acak 40 karakter) |
| `user_agent` | `string(512)`, nullable |
| `ip_address` | `string(45)`, nullable |
| `last_used_at` | timestamp, nullable |
| `expires_at` | timestamp |
| `created_at`, `updated_at` | timestamps |

- Cookie `trusted_device` berisi token mentah: dienkripsi Laravel, `HttpOnly`, `Secure` (production), `SameSite=Lax`, umur 30 hari.
- Perangkat valid jika: hash cocok, `user_id` cocok, dan `expires_at` > sekarang.
- Baris yang kedaluwarsa dihapus oleh scheduled command harian (`trusted-devices:prune`).

### 3.3 Yang memakai tabel yang sudah ada

- Notifikasi memakai tabel `notifications`.
- Sesi aktif memakai tabel `sessions` (`SESSION_DRIVER=database`) untuk menghapus semua sesi user.

## 4. Notifikasi Login Baru & "Bukan saya"

### 4.1 Notifikasi

Semuanya `ShouldQueue`, dengan channel `database` + `mail`, berbahasa Indonesia, waktu dalam WIB.

| Notifikasi | Pemicu |
|---|---|
| `NewLoginNotification` | Login berhasil lewat halaman challenge (perangkat belum tepercaya) |
| `TwoFactorEnabledNotification` | Setup 2FA selesai |
| `RecoveryCodeUsedNotification` | Login dengan recovery code |
| `TwoFactorResetByAdminNotification` | Admin me-reset 2FA user |

Isi `NewLoginNotification`: waktu, browser + OS (diurai dari user agent oleh helper kecil `UserAgentSummary`, tanpa dependensi baru), IP, dan metode (password/Google + TOTP/recovery code). Notifikasi ini membawa tombol "Bukan saya".

Notifikasi **tidak** dikirim untuk login dari perangkat tepercaya.

### 4.2 Alur "Bukan saya"

- Link berupa `URL::temporarySignedRoute('security.not-me', now()->addHours(24), ['user' => $id])`.
- `GET /keamanan/bukan-saya/{user}` (middleware `signed`): **hanya** menampilkan halaman konfirmasi. GET tidak boleh mengubah data, karena pemindai link di layanan email akan membukanya otomatis.
- `POST` ke URL bertanda tangan yang sama, setelah user menekan "Ya, amankan akun saya", menjalankan `SecureAccountAction`:
  1. Hapus semua baris `sessions` milik user.
  2. Hapus semua `trusted_devices` milik user.
  3. Reset 2FA: kosongkan keempat kolom `two_factor_*`.
  4. Rotasi `remember_token`.
  5. Kirim link reset password ke email user (`Password::sendResetLink`) dan tampilkan halaman "Akun sudah diamankan, cek email". Jika `google_id` terisi, halaman itu juga menampilkan saran untuk mengamankan akun Google mereka.
- Link yang kedaluwarsa atau tidak valid menampilkan halaman "Link kedaluwarsa" dengan tautan ke Lupa Password.

## 5. Auto-Logout

**"Tidak aktif"** berarti tidak ada event `mousemove`, `keydown`, `pointerdown`, `scroll`, atau `touchstart` di tab mana pun milik aplikasi.

### 5.1 Server: middleware `LogoutIdleSessions`

- Ditambahkan ke grup `web` dan hanya berlaku untuk user yang sudah login.
- Menyimpan `last_activity_at` di session. Jika `now - last_activity_at > config('auth.idle_timeout')` (default 300 detik), middleware akan:
  - `Auth::logout()`, `invalidate()`, `regenerateToken()`
  - untuk request biasa, redirect ke halaman login yang sesuai (wisatawan/admin) dengan flash "Kamu keluar otomatis karena tidak aktif selama 5 menit."
  - untuk request JSON/AJAX, mengembalikan `401` dengan `{"reason":"idle_timeout"}`.
- Request dengan header `X-Background-Request: 1` **tidak** memperbarui `last_activity_at`.
- Konfigurasi: `AUTH_IDLE_TIMEOUT=300` → `config/auth.php` key `idle_timeout`.

### 5.2 Endpoint

- `POST /sesi/ping`: memperbarui `last_activity_at` dan mengembalikan `204`. Dipanggil browser saat user aktif.
- Logout memakai route `logout` yang sudah ada.

### 5.3 Browser: komponen Alpine `idleTimeout`

Dimuat di layout wisatawan dan admin hanya jika user login. Nilai timeout dikirim dari server lewat atribut data.

- Aktivitas user memperbarui `lastActivity` dan dibagikan ke tab lain lewat `BroadcastChannel('jogjatrip-idle')`, dengan fallback event `storage`.
- Ping ke `/sesi/ping` paling sering sekali per 60 detik, hanya jika ada aktivitas sejak ping terakhir.
- Saat diam `timeout - 60` detik: tampilkan modal hitung mundur 60 detik dengan tombol "Tetap masuk" (yang memanggil ping dan menutup modal).
- Saat diam `timeout` detik: submit form logout, lalu siarkan `logout` ke tab lain agar ikut pindah ke halaman login.
- Respons `401` dengan `idle_timeout` dari fetch mana pun memicu redirect ke halaman login.

## 6. Panel Profil & Admin

### 6.1 Profil › bagian "Keamanan"

- Status 2FA (aktif sejak tanggal X) dan jumlah recovery code tersisa.
- "Buat ulang recovery codes": wajib mengisi kode TOTP yang sedang berlaku (berlaku sama untuk user password maupun Google). Kode lama otomatis tidak berlaku.
- Daftar perangkat tepercaya (browser/OS, IP, terakhir dipakai, kedaluwarsa), dengan tombol "Cabut" per perangkat dan "Cabut semua".
- Banner di semua halaman jika recovery code ≤ 2.

### 6.2 Admin › Users

- Aksi "Reset 2FA" per user (dengan konfirmasi): mengosongkan kolom `two_factor_*`, menghapus perangkat tepercaya dan sesi user tersebut, lalu mengirim `TwoFactorResetByAdminNotification`.
- Admin tidak dapat me-reset 2FA miliknya sendiri lewat aksi ini.

## 7. Error Handling

| Situasi | Perilaku |
|---|---|
| Kode salah | Pesan "Kode salah". Setelah 5 kali gagal per menit, user dikunci dengan hitung mundur. |
| Jam HP tidak sinkron | Toleransi ±1 slot; pesan bantuan "Pastikan jam HP kamu disetel otomatis". |
| HP & recovery code hilang | Admin melakukan Reset 2FA (§6.2). |
| Pending challenge kedaluwarsa | Redirect ke login dengan pesan "Waktu habis". |
| Email gagal terkirim | Login tetap berhasil; job di-retry oleh queue (3 kali); notifikasi in-app tetap tersimpan. |
| Cookie perangkat palsu/dicabut/kedaluwarsa | Diperlakukan sebagai perangkat baru (wajib TOTP). Cookie dihapus. |
| Signed URL "Bukan saya" tidak valid | Halaman "Link kedaluwarsa". |
| Secret di session hilang saat setup | Secret baru dibuat dan QR baru ditampilkan. |

## 8. Testing

PHPUnit feature test di `tests/Feature/Auth/TwoFactor/`, ditulis dengan TDD. TOTP yang valid dibuat dengan `Google2FA::getCurrentOtp($secret)` di test.

- **Gerbang:** ketiga pintu masuk × tiga cabang (belum setup / tepercaya / challenge). Google memakai Socialite yang di-mock.
- **Middleware wajib-setup:** route ber-auth wisatawan dan admin diarahkan ke setup; logout dan ping tetap bisa diakses.
- **Challenge:** kode benar, kode salah, replay ditolak, toleransi ±1 slot, rate limit, pending kedaluwarsa, recovery code sekali pakai.
- **Setup:** secret tidak tersimpan sebelum konfirmasi, recovery code dibuat 8 buah, notifikasi terkirim.
- **Perangkat tepercaya:** valid, kedaluwarsa, dicabut, token palsu, milik user lain, prune command.
- **Notifikasi:** `Notification::fake()`; terkirim hanya untuk perangkat baru; isi browser/OS/IP benar.
- **"Bukan saya":** GET tanpa efek samping; POST menghapus sesi, perangkat, dan 2FA; signature invalid/kedaluwarsa ditolak; redirect ke form reset password dengan token valid.
- **Idle timeout:** `travel(299)->seconds()` masih login; `travel(301)->seconds()` logout + redirect; JSON mengembalikan 401; header background tidak memperpanjang; ping memperpanjang.
- **Remember-me:** form tidak memiliki checkbox; login tidak membuat cookie remember.
- **Admin reset 2FA:** berhasil untuk user lain, ditolak untuk diri sendiri.

**Checklist manual (JavaScript auto-logout)**, karena repo belum memiliki test JS:
1. Diam 4 menit → modal muncul dengan hitung mundur.
2. Klik "Tetap masuk" → modal tertutup, sesi diperpanjang.
3. Diam 5 menit → logout + pesan di halaman login.
4. Dua tab: aktif di tab A → tab B tidak logout; logout di A → B ikut pindah.
5. Scroll terus 6 menit tanpa klik → tetap login.
6. Tutup laptop 6 menit lalu buka → request berikutnya diarahkan ke login.

## 9. Deploy

Tambahan ke `docs/deployment/PAAS-GUIDE.md` dan `IAAS-GUIDE.md`:

- Isi `MAIL_MAILER` dan kredensial provider (Resend/Mailgun/SMTP); `MAIL_FROM_ADDRESS` memakai domain sendiri.
- Pastikan queue worker (`php artisan queue:work`) dan scheduler berjalan.
- HTTPS wajib; set `SESSION_SECURE_COOKIE=true`.
- `AUTH_IDLE_TIMEOUT=300`.
- **Jangan mengganti `APP_KEY`** setelah ada user yang setup 2FA, karena secret yang terenkripsi tidak akan bisa dibaca lagi.
- Setelah deploy, semua user lama akan diminta setup 2FA saat login berikutnya.

## 10. Dependensi Baru

- `pragmarx/google2fa` (TOTP)
- `bacon/bacon-qr-code` (QR SVG)
