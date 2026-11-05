# 2FA (TOTP), Notifikasi Login Baru & Auto-Logout — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Mewajibkan 2FA TOTP untuk semua akun, mengirim notifikasi login dari perangkat baru dengan tombol "Bukan saya", dan me-logout otomatis setelah 5 menit tidak aktif.

**Architecture:** Ketiga pintu masuk (wisatawan, admin, Google) memvalidasi kredensial **tanpa** login, lalu menyerahkan user ke `TwoFactorGate`. Gerbang ini memilih salah satu: setup wajib, perangkat tepercaya, atau halaman challenge. Logika TOTP/recovery code ada di `TwoFactorAuthenticator`, cookie perangkat di `TrustedDeviceManager`, dan "kunci akun" di `SecureAccountAction`. Dua middleware global di grup `web` (`LogoutIdleSessions`, `EnsureTwoFactorSetUp`) menjaga sesi, sedangkan komponen Alpine menangani peringatan idle di browser.

**Tech Stack:** Laravel 12.69, PHP 8.2, Breeze (Blade + Alpine.js + Tailwind), PHPUnit (sqlite in-memory), `pragmarx/google2fa`, `bacon/bacon-qr-code`.

**Spec:** `docs/superpowers/specs/2026-10-30-two-factor-auth-design.md`

## Global Constraints

- Semua teks UI, email, dan pesan error **berbahasa Indonesia**; waktu di email/notifikasi dalam **WIB** (`Asia/Jakarta`).
- TOTP: 6 digit, periode 30 detik, toleransi **±1 slot**, anti-replay lewat `two_factor_last_used_step`.
- Recovery code: **8 buah**, format `xxxxx-xxxxx` (huruf kecil + angka), disimpan sebagai hash `Hash::make`, sekali pakai.
- Pending challenge kedaluwarsa **5 menit**; rate limit challenge **5 percobaan / 60 detik** per `user_id|ip`.
- Perangkat tepercaya: **30 hari**, cookie `trusted_device` (HttpOnly, `Secure` mengikuti `config('session.secure')`, SameSite=Lax), DB hanya menyimpan SHA-256.
- Idle timeout: `config('auth.idle_timeout')` dari `AUTH_IDLE_TIMEOUT`, default **300** detik; modal muncul pada `timeout - 60`.
- Link "Bukan saya": signed URL **24 jam**; GET tidak boleh mengubah data.
- Kolom `two_factor_*` tidak boleh masuk `$fillable` dan wajib masuk `$hidden`.
- Remember-me dihapus total; tidak ada cookie recaller yang dibuat oleh login mana pun.
- Semua notifikasi baru `implements ShouldQueue`, `via()` = `['database', 'mail']`, dan `toArray()` wajib punya key `message` (dipakai `notifications/index.blade.php`).
- Ke-68 test yang ada saat ini harus tetap lulus (diubah hanya jika perilaku yang diuji memang diubah oleh spec).

## Review Focus

1. **Reload halaman setup** → secret dan QR harus **sama** (jika tidak, QR yang sudah di-scan tidak valid lagi). Test di Task 5.
2. **Kode TOTP diketik dengan spasi** (`"123 456"`) → spasi dibuang, kode tetap diterima. Test di Task 1.
3. **Recovery code huruf besar / spasi di ujung** (`" ABCDE-12345 "`) → dinormalisasi, kode tetap diterima. Test di Task 1.
4. **User dihapus atau 2FA-nya di-reset admin saat sedang di halaman challenge** → diperlakukan seperti pending kedaluwarsa, bukan error 500. Test di Task 6.
5. **Link "Bukan saya" diklik dua kali** (POST kedua dalam 24 jam) → tetap berhasil tanpa error dan kembali ke form reset password. Test di Task 3.

---

## File Structure

| File | Tanggung jawab |
|---|---|
| `app/Services/Auth/TwoFactorAuthenticator.php` | Secret, QR SVG, verifikasi TOTP, recovery code |
| `app/Services/Auth/TrustedDeviceManager.php` | Membuat/mengecek/mencabut perangkat tepercaya + cookie |
| `app/Services/Auth/TwoFactorGate.php` | Memilih cabang setelah kredensial valid |
| `app/Actions/Auth/SecureAccountAction.php` | Hapus sesi + perangkat + 2FA + rotasi remember_token |
| `app/Models/TrustedDevice.php` | Model |
| `app/Support/UserAgentSummary.php` | "Chrome di Windows" |
| `app/Http/Middleware/EnsureTwoFactorSetUp.php` | Paksa setup |
| `app/Http/Middleware/LogoutIdleSessions.php` | Idle timeout server |
| `app/Http/Controllers/Auth/TwoFactorSetupController.php` | Setup + halaman recovery codes |
| `app/Http/Controllers/Auth/TwoFactorChallengeController.php` | Challenge |
| `app/Http/Controllers/Auth/NotMeController.php` | Alur "Bukan saya" |
| `app/Http/Controllers/SessionPingController.php` | `POST /sesi/ping` |
| `app/Http/Controllers/Profile/TwoFactorSecurityController.php` | Regenerate codes, cabut perangkat |
| `app/Console/Commands/PruneTrustedDevices.php` | `trusted-devices:prune` |
| `app/Notifications/{NewLogin,TwoFactorEnabled,RecoveryCodeUsed,TwoFactorResetByAdmin}Notification.php` | Notifikasi |
| `resources/js/idle-timeout.js` | Komponen Alpine `idleTimeout` |
| `resources/views/auth/two-factor/{setup,recovery-codes,challenge}.blade.php` | View 2FA |
| `resources/views/auth/not-me/{confirm,expired}.blade.php` | View "Bukan saya" |
| `resources/views/partials/{idle-timeout,recovery-codes-banner}.blade.php` | Dimuat di layout app & admin |
| `resources/views/profile/partials/two-factor-security.blade.php` | Bagian Keamanan di Profil |

---

### Task 1: Fondasi — dependensi, migrasi, model, `TwoFactorAuthenticator`

**Files:**
- Modify: `composer.json`/`composer.lock` (lewat composer)
- Create: `database/migrations/2026_10_30_000001_add_two_factor_columns_to_users_table.php`
- Modify: `app/Models/User.php`, `database/factories/UserFactory.php`
- Create: `app/Services/Auth/TwoFactorAuthenticator.php`
- Test: `tests/Feature/Auth/TwoFactor/TwoFactorAuthenticatorTest.php`

**Interfaces — Produces:**
- `User::hasTwoFactorEnabled(): bool` (`two_factor_confirmed_at !== null`)
- `User::recoveryCodesRemaining(): int`
- Cast: `two_factor_secret` → `encrypted`, `two_factor_recovery_codes` → `encrypted:array`, `two_factor_confirmed_at` → `datetime`, `two_factor_last_used_step` → `integer`.
- `UserFactory` **default state**: 2FA aktif (secret dibuat `TwoFactorAuthenticator::generateSecret()`, `two_factor_confirmed_at = now()`, recovery codes `[]`). State `withoutTwoFactor()` mengosongkan keempat kolom. State `withRecoveryCodes(array $plain)` menyimpan hash dari kode plain.
- `TwoFactorAuthenticator`:
  - `generateSecret(): string`
  - `qrCodeSvg(User $user, string $secret): string` (otpauth: issuer `config('app.name')`, label email; SVG 192px)
  - `verifySecretCode(string $secret, string $code, ?int $lastUsedStep): ?int` (mengembalikan timestep jika valid, `null` jika tidak)
  - `verifyUserCode(User $user, string $code): bool` (memakai secret user; jika valid, simpan `two_factor_last_used_step`)
  - `generateRecoveryCodes(): array` → `['plain' => list<string>, 'hashed' => list<string>]`
  - `useRecoveryCode(User $user, string $code): bool` (hapus hash yang cocok lalu save)
  - `disable(User $user): void` (kosongkan keempat kolom tanpa save terpisah; memakai `forceFill(...)->save()`)

- [ ] **Step 1:** `composer require pragmarx/google2fa bacon/bacon-qr-code`. Expected: terpasang tanpa konflik.

- [ ] **Step 2: Tulis test yang gagal** di `TwoFactorAuthenticatorTest` (RefreshDatabase):

```php
test_generated_secret_is_base32_of_at_least_16_chars()       // matches /^[A-Z2-7]{16,}$/
test_valid_current_code_returns_timestep()                   // assertIsInt(verifySecretCode($s, Google2FA::getCurrentOtp($s), null))
test_code_from_previous_slot_is_accepted()                   // otp dari timestamp now-30s → not null
test_code_two_slots_old_is_rejected()                        // otp dari now-90s → null
test_code_with_spaces_is_accepted()                          // "123 456" format dari otp valid → not null      (Review Focus 2)
test_same_code_cannot_be_used_twice_for_user()               // verifyUserCode true lalu false untuk kode yang sama
test_recovery_codes_are_eight_in_expected_format()           // count 8, each matches /^[a-z0-9]{5}-[a-z0-9]{5}$/, hashed !== plain
test_recovery_code_is_single_use()                           // useRecoveryCode true, lalu false; recoveryCodesRemaining() == 7
test_recovery_code_is_case_and_whitespace_insensitive()      // " ".strtoupper($plain)." " → true     (Review Focus 3)
test_two_factor_columns_are_hidden_and_secret_encrypted()    // toArray() tanpa key two_factor_*; raw DB value !== secret
test_factory_default_has_two_factor_and_state_can_remove_it()
```

Gunakan `new \PragmaRX\Google2FA\Google2FA()` di test (`getCurrentOtp`, `oathTotp($secret, $timestep)` untuk slot lain).

- [ ] **Step 3:** Jalankan `php artisan test --filter=TwoFactorAuthenticatorTest`. Expected: FAIL (class tidak ada).

- [ ] **Step 4:** Buat migrasi (kolom sesuai spec §3.1; `down()` drop keempatnya). Perbarui `User` (casts, `$hidden`, dua method), `UserFactory`, dan `TwoFactorAuthenticator`. Implementasi: `verifySecretCode` membuang semua whitespace, lalu `Google2FA::verifyKeyNewer($secret, $code, $lastUsedStep ?? 0, 1)`; hasil `false` → `null`. Normalisasi recovery code: `strtolower(trim($code))`, dicek dengan `Hash::check` ke tiap hash.

- [ ] **Step 5:** `php artisan test`. Expected: test baru PASS **dan** 68 test lama tetap PASS.

- [ ] **Step 6: Commit** — `git commit -m "feat(2fa): add TOTP columns, TwoFactorAuthenticator service and factory states"`

---

### Task 2: Perangkat tepercaya

**Files:**
- Create: migrasi `2026_10_30_000002_create_trusted_devices_table.php`, `app/Models/TrustedDevice.php`, `app/Services/Auth/TrustedDeviceManager.php`, `app/Console/Commands/PruneTrustedDevices.php`
- Modify: `app/Models/User.php` (relasi), `routes/console.php` (jadwal)
- Test: `tests/Feature/Auth/TwoFactor/TrustedDeviceTest.php`

**Interfaces:**
- Consumes: kolom/relasi dari Task 1.
- Produces:
  - `User::trustedDevices(): HasMany`
  - `TrustedDeviceManager::COOKIE = 'trusted_device'`, `TrustedDeviceManager::DAYS = 30`
  - `trust(User $user, Request $request): TrustedDevice` (token `Str::random(40)`, simpan `hash('sha256', $token)`, `Cookie::queue(...)`)
  - `isTrusted(User $user, Request $request): bool` (valid → update `last_used_at`; cookie ada tapi tidak valid → `Cookie::queue(Cookie::forget(self::COOKIE))`)
  - `revoke(TrustedDevice $device): void`, `revokeAll(User $user): int`
  - Command `trusted-devices:prune` menghapus `expires_at < now()`, dijadwalkan `->daily()`.

- [ ] **Step 1: Tulis test yang gagal:**

```php
test_trust_stores_only_hash_and_queues_cookie()          // DB token_hash length 64, cookie trusted_device ada, nilai cookie !== token_hash
test_trusted_cookie_is_recognised_for_same_user()         // request dengan cookie → isTrusted true, last_used_at terisi
test_cookie_of_another_user_is_not_trusted()
test_expired_device_is_not_trusted()                      // travel(31)->days()
test_revoked_device_is_not_trusted()
test_forged_cookie_is_not_trusted_and_cookie_is_forgotten()
test_prune_command_deletes_only_expired_devices()         // artisan('trusted-devices:prune')->assertSuccessful()
```

Untuk mengirim cookie di test: `$this->withCookie(TrustedDeviceManager::COOKIE, $token)`. Uji manager lewat route uji sementara yang didefinisikan di test (`Route::get('/_t', fn (Request $r) => ['trusted' => app(TrustedDeviceManager::class)->isTrusted(User::find(1), $r)])->middleware('web')`).

- [ ] **Step 2:** `php artisan test --filter=TrustedDeviceTest`. Expected: FAIL.
- [ ] **Step 3:** Implementasikan migrasi (spec §3.2), model (casts `last_used_at`/`expires_at` datetime, `belongsTo(User)`), manager, command, dan jadwal.
- [ ] **Step 4:** `php artisan test`. Expected: semua PASS.
- [ ] **Step 5: Commit** — `feat(2fa): add trusted devices with hashed cookie tokens and prune command`

---

### Task 3: `SecureAccountAction` & alur "Bukan saya"

**Files:**
- Create: `app/Actions/Auth/SecureAccountAction.php`, `app/Http/Controllers/Auth/NotMeController.php`, `resources/views/auth/not-me/confirm.blade.php`, `resources/views/auth/not-me/expired.blade.php`
- Modify: `routes/auth.php`, `resources/views/auth/reset-password.blade.php` (hint Google)
- Test: `tests/Feature/Auth/TwoFactor/NotMeTest.php`

**Interfaces:**
- Consumes: `TwoFactorAuthenticator::disable`, `TrustedDeviceManager::revokeAll`.
- Produces:
  - `SecureAccountAction::handle(User $user): void`: hapus baris `DB::table(config('session.table'))` milik user, `revokeAll`, `disable`, `remember_token = Str::random(60)`.
  - Route `GET /keamanan/bukan-saya/{user}` → `security.not-me` dan `POST /keamanan/bukan-saya/{user}` → `security.not-me.confirm` (keduanya di luar grup guest/auth). Signature dicek manual dengan `$request->hasValidSignature()`; jika tidak valid, kembalikan view `expired` dengan status 403.
  - Form konfirmasi memakai `action="{{ request()->fullUrl() }}"` agar signature ikut ter-POST.
  - Setelah POST: jika user yang sedang login adalah user tersebut → `Auth::logout()` + invalidate session; lalu `redirect()->route('password.reset', ['token' => Password::createToken($user), 'email' => $user->email])->with('google_hint', $user->google_id !== null)`.
  - Link dibuat oleh pemanggil (Task 4): `URL::temporarySignedRoute('security.not-me', now()->addHours(24), ['user' => $user->id])`.

- [ ] **Step 1: Tulis test yang gagal:**

```php
test_get_shows_confirmation_without_changing_anything()     // assertOk; 2FA, trusted devices, sessions row tetap ada
test_invalid_or_expired_signature_shows_expired_page()      // travel(25)->hours() → assertStatus(403)->assertSee('Link kedaluwarsa')
test_post_secures_account()                                 // sessions rows user hilang (insert manual ke tabel sessions), trusted devices 0, hasTwoFactorEnabled false, remember_token berubah
test_post_redirects_to_password_reset_with_valid_token()    // redirect starts with route('password.reset', ...); Password::tokenExists($user, $token)
test_post_logs_out_current_user_if_same()                   // actingAs($user) → assertGuest()
test_clicking_twice_still_succeeds()                        // POST dua kali → keduanya redirect ke password.reset   (Review Focus 5)
test_reset_page_shows_google_hint_when_flagged()            // user dengan google_id → session google_hint true dan teks "akun Google" terlihat
```

- [ ] **Step 2:** `php artisan test --filter=NotMeTest`. Expected: FAIL.
- [ ] **Step 3:** Implementasikan action, controller, route, dan view (pakai `x-guest-layout` seperti view auth lain). Teks tombol: **"Ya, amankan akun saya"**. Hint di reset-password: "Kamu biasa masuk dengan Google? Amankan juga akun Google-mu di myaccount.google.com/security."
- [ ] **Step 4:** `php artisan test`. Expected: semua PASS.
- [ ] **Step 5: Commit** — `feat(2fa): add "not me" signed link flow that locks down the account`

---

### Task 4: `UserAgentSummary` & empat notifikasi

**Files:**
- Create: `app/Support/UserAgentSummary.php`, 4 file notifikasi di `app/Notifications/`
- Modify: `resources/views/notifications/index.blade.php` (tampilkan tombol jika `data['action_url']` ada)
- Test: `tests/Unit/UserAgentSummaryTest.php`, `tests/Feature/Auth/TwoFactor/SecurityNotificationsTest.php`

**Interfaces — Produces:**
- `UserAgentSummary::summarize(?string $userAgent): string` → `"{Browser} di {OS}"`; jika tidak dikenali: `"Browser tidak dikenal"`. Urutan deteksi browser: `Edg/` → Edge, `OPR/` → Opera, `Firefox/`, `Chrome/`, `Safari/`. OS: `Windows`, `Android`, `iPhone|iPad` → iOS, `Mac OS X` → macOS, `Linux`.
- `NewLoginNotification(string $ipAddress, string $device, string $methodLabel, CarbonInterface $at)`: `toArray` berisi `message` ("Login baru dari {device} ({ip})"), `action_url` (signed URL "Bukan saya" dari Task 3), `action_label` "Bukan saya", plus field mentah. Mail subject: **"Login baru ke akun JogjaTrip kamu"**, dengan tombol "Bukan saya".
- `TwoFactorEnabledNotification()`: subject **"2FA telah diaktifkan di akunmu"**; juga membawa link "Bukan saya".
- `RecoveryCodeUsedNotification(int $remaining)`: subject **"Recovery code dipakai untuk masuk"**; message menyebut sisa N kode.
- `TwoFactorResetByAdminNotification()`: subject **"2FA akunmu telah di-reset oleh Admin"**.
- Semua: `ShouldQueue`, `public int $tries = 3;`, `via` `['database','mail']`, waktu `->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i').' WIB'`.

- [ ] **Step 1: Tulis test yang gagal:** dataset `UserAgentSummaryTest` (Chrome/Windows, Edge/Windows, Firefox/Linux, Safari/iOS, Chrome/Android, null → "Browser tidak dikenal"); `SecurityNotificationsTest`: tiap notifikasi `assertInstanceOf(ShouldQueue::class)`, `via` benar, `toArray` punya `message`, `action_url` NewLogin adalah signed URL valid (`URL::hasValidSignature(Request::create($url))`), dan mail subject sesuai teks di atas.
- [ ] **Step 2:** Jalankan. Expected: FAIL.
- [ ] **Step 3:** Implementasi + tombol aksi di view notifikasi.
- [ ] **Step 4:** `php artisan test`. Expected: PASS.
- [ ] **Step 5: Commit** — `feat(2fa): add security notifications and user agent summary`

---

### Task 5: Setup 2FA & middleware `EnsureTwoFactorSetUp`

**Files:**
- Create: `app/Http/Controllers/Auth/TwoFactorSetupController.php`, `app/Http/Middleware/EnsureTwoFactorSetUp.php`, `resources/views/auth/two-factor/setup.blade.php`, `resources/views/auth/two-factor/recovery-codes.blade.php`
- Modify: `routes/auth.php`, `bootstrap/app.php` (`$middleware->appendToGroup('web', EnsureTwoFactorSetUp::class)`)
- Test: `tests/Feature/Auth/TwoFactor/TwoFactorSetupTest.php`

**Interfaces:**
- Consumes: `TwoFactorAuthenticator` (Task 1), `TwoFactorEnabledNotification` (Task 4).
- Produces routes (middleware `auth`):
  - `GET /dua-faktor/aktifkan` → `two-factor.setup` (jika sudah aktif → redirect ke tujuan default)
  - `POST /dua-faktor/aktifkan` → `two-factor.setup.store` (field `code`)
  - `GET /dua-faktor/recovery-codes` → `two-factor.recovery-codes` (menampilkan `session('two_factor.recovery_codes_plain')`; jika kosong → redirect ke tujuan default)
  - `POST /dua-faktor/recovery-codes` → `two-factor.recovery-codes.ack` (forget kode dari session, `redirect()->intended(default)`)
- Session key secret sementara: `two_factor.setup_secret` (dibuat sekali, dipakai ulang saat reload).
- Tujuan default: `isAdmin()` → `admin.dashboard`, selain itu → `home`.
- Middleware: jika `Auth::check()` dan `! hasTwoFactorEnabled()` dan route **bukan** salah satu dari `two-factor.setup*`, `two-factor.recovery-codes*`, `logout`, `session.ping`, `security.not-me*` → JSON: `403 {"reason":"two_factor_setup_required"}`, selain itu `redirect()->route('two-factor.setup')`. (Spec §2.5: semua request.) Middleware di-append ke grup `web`, sehingga route `session.ping` (Task 8) otomatis tercakup oleh allow-list ini.

- [ ] **Step 1: Tulis test yang gagal:**

```php
test_user_without_2fa_is_redirected_to_setup_from_any_page()     // withoutTwoFactor; get(route('favorites.index')) dan get('/admin') untuk admin → redirect setup
test_logout_is_allowed_without_2fa()
test_setup_page_shows_qr_and_secret()                             // assertSee('<svg', false)
test_reloading_setup_page_keeps_same_secret()                     // dua GET → session('two_factor.setup_secret') sama   (Review Focus 1)
test_secret_is_not_saved_before_confirmation()                    // setelah GET, user->two_factor_secret null
test_wrong_code_shows_error_and_does_not_enable()                 // assertSessionHasErrors('code')
test_correct_code_enables_2fa_and_shows_eight_recovery_codes()    // confirmed_at not null; redirect recovery-codes; 8 kode terlihat; recoveryCodesRemaining 8
test_enabling_sends_two_factor_enabled_notification()             // Notification::fake
test_acknowledging_codes_clears_them_and_redirects_to_intended()
test_user_with_2fa_visiting_setup_is_redirected_home()
```

- [ ] **Step 2:** Jalankan. Expected: FAIL.
- [ ] **Step 3:** Implementasi. View setup: QR (`{!! $qrSvg !!}`), secret dalam grup 4 karakter untuk input manual, input `code` (`inputmode="numeric" autocomplete="one-time-code"`). View recovery-codes: daftar kode, tombol **"Salin"** (Clipboard API), **"Unduh .txt"** (Blob, nama file `jogjatrip-recovery-codes.txt`), dan tombol submit **"Saya sudah menyimpannya"**.
- [ ] **Step 4:** `php artisan test`. Expected: semua PASS (test lama aman karena factory default sudah ber-2FA).
- [ ] **Step 5: Commit** — `feat(2fa): add mandatory TOTP setup flow and enforcement middleware`

---

### Task 6: `TwoFactorGate`, halaman challenge, login wisatawan, hapus remember-me

**Files:**
- Create: `app/Services/Auth/TwoFactorGate.php`, `app/Http/Controllers/Auth/TwoFactorChallengeController.php`, `resources/views/auth/two-factor/challenge.blade.php`
- Modify: `app/Http/Requests/Auth/LoginRequest.php`, `app/Http/Controllers/Auth/AuthenticatedSessionController.php`, `resources/views/auth/login.blade.php`, `resources/views/auth/admin-login.blade.php` (hapus checkbox), `routes/auth.php`, `tests/Feature/Auth/AuthenticationTest.php`
- Test: `tests/Feature/Auth/TwoFactor/TwoFactorChallengeTest.php`

**Interfaces:**
- Consumes: Task 1, 2, 4, 5.
- Produces:
  - `LoginRequest::validatedUser(): User`: rate limit Breeze tetap berjalan; memakai `Auth::getProvider()->retrieveByCredentials()` + `validateCredentials()` **tanpa login**; method `authenticate()` lama dihapus.
  - `TwoFactorGate::pass(Request $request, User $user, string $method, string $intended): RedirectResponse` dengan `$method` ∈ `'password'|'google'`. Selalu memanggil `$request->session()->regenerate()`. Cabang sesuai spec §2.2. Pending disimpan di `session('two_factor.pending') = ['user_id', 'method', 'intended', 'expires_at' => now()->addMinutes(5)->timestamp]`.
  - Routes (middleware `guest`): `GET /dua-faktor` → `two-factor.challenge`, `POST /dua-faktor` → `two-factor.challenge.store` (field `code` **atau** `recovery_code`, plus `trust_device` boolean).
  - Pending tidak ada, kedaluwarsa, user hilang, atau user tidak lagi `hasTwoFactorEnabled()` → forget pending, lalu redirect ke `login` (atau `admin.login` jika user admin) dengan error `email`: **"Waktu habis, silakan masuk lagi."**
  - Berhasil: `Auth::login($user)` (tanpa remember), regenerate, `trust()` jika dicentang, `NewLoginNotification` dengan method label (`Password` / `Google` + ` + kode authenticator` / ` + recovery code`), `RecoveryCodeUsedNotification` jika memakai recovery code, lalu `redirect($pending['intended'])`.
  - Rate limiter key `'two-factor:'.$userId.'|'.$request->ip()`, 5/60 detik; pesan: **"Terlalu banyak percobaan. Coba lagi dalam :seconds detik."**
  - Kode salah: **"Kode salah."** + bantuan **"Pastikan jam HP kamu disetel otomatis."**

- [ ] **Step 1: Tulis test yang gagal:**

```php
test_login_with_2fa_and_untrusted_device_goes_to_challenge_and_is_not_authenticated()
test_login_without_2fa_logs_in_and_redirects_to_setup()
test_login_with_trusted_device_logs_in_directly_without_notification()
test_correct_code_logs_in_sends_new_login_notification_and_redirects_to_intended()
test_wrong_code_is_rejected()
test_replayed_code_is_rejected()
test_recovery_code_logs_in_and_sends_recovery_code_used_notification()
test_trust_device_checkbox_creates_trusted_device()
test_sixth_attempt_within_a_minute_is_throttled()
test_expired_pending_redirects_to_login_with_message()            // travel(6)->minutes()
test_pending_for_deleted_or_reset_user_redirects_to_login()       // (Review Focus 4)
test_challenge_without_pending_redirects_to_login()
test_login_never_sets_remember_cookie()                           // assertCookieMissing(Auth::guard()->getRecallerName())
test_login_form_has_no_remember_checkbox()                        // assertDontSee('name="remember"', false)
```

- [ ] **Step 2:** Jalankan. Expected: FAIL.
- [ ] **Step 3:** Implementasi. `AuthenticatedSessionController@store`: `$user = $request->validatedUser()`; jika admin → `ValidationException` dengan pesan lama (tanpa logout karena belum login); lalu `return $gate->pass($request, $user, 'password', $request->session()->pull('url.intended', route('home')))`.
- [ ] **Step 4:** Perbarui `AuthenticationTest` (login sukses sekarang mengarah ke `two-factor.challenge` dan `assertGuest()`). `php artisan test`. Expected: semua PASS.
- [ ] **Step 5: Commit** — `feat(2fa): add TwoFactorGate, challenge page, and remove remember-me`

---

### Task 7: Gerbang untuk login Admin & Google

**Files:**
- Modify: `app/Http/Controllers/Admin/AdminAuthenticatedSessionController.php`, `app/Http/Controllers/Auth/GoogleAuthController.php`, `tests/Feature/Auth/GoogleAuthTest.php`, test login admin di `tests/Feature/Admin/` yang terdampak
- Test: `tests/Feature/Auth/TwoFactor/TwoFactorEntryPointsTest.php`

**Interfaces — Consumes:** `LoginRequest::validatedUser()`, `TwoFactorGate::pass()` (Task 6).

- [ ] **Step 1: Tulis test yang gagal:**

```php
test_admin_login_goes_through_challenge()                         // assertRedirect(route('two-factor.challenge')); assertGuest
test_admin_without_2fa_is_sent_to_setup()
test_admin_login_still_rejects_traveler()                         // pesan lama "bukan akun Admin"
test_expired_admin_pending_redirects_to_admin_login()
test_new_google_user_is_sent_to_setup()
test_existing_google_user_with_2fa_goes_to_challenge()
test_existing_google_user_with_trusted_device_logs_in_directly()
test_google_login_never_sets_remember_cookie()
```

- [ ] **Step 2:** Jalankan. Expected: FAIL.
- [ ] **Step 3:** Ganti `$request->authenticate()` + login/logout di admin controller dengan `validatedUser()` + `pass($request, $user, 'password', ...)` (default `route('admin.dashboard')`). Di Google: ganti `Auth::login($user, true)` dengan `return $gate->pass($request, $user, 'google', session()->pull('url.intended', route('home')))`.
- [ ] **Step 4:** Perbarui ekspektasi `GoogleAuthTest` dan test login admin yang ada sesuai cabang baru. `php artisan test`. Expected: semua PASS.
- [ ] **Step 5: Commit** — `feat(2fa): route admin and Google logins through TwoFactorGate`

---

### Task 8: Idle timeout — server

**Files:**
- Create: `app/Http/Middleware/LogoutIdleSessions.php`, `app/Http/Controllers/SessionPingController.php`
- Modify: `config/auth.php` (`'idle_timeout' => (int) env('AUTH_IDLE_TIMEOUT', 300)`), `bootstrap/app.php` (append ke `web` **sebelum** `EnsureTwoFactorSetUp`), `routes/web.php`, `AuthenticatedSessionController@destroy`, `.env.example`
- Test: `tests/Feature/Auth/IdleTimeoutTest.php`

**Interfaces — Produces:**
- Session key `auth.last_activity_at` (unix timestamp). Jika belum ada → isi dengan sekarang (supaya `actingAs` di test lama tidak langsung logout).
- Idle → `Auth::logout()`, `invalidate()`, `regenerateToken()`; JSON (`$request->expectsJson()`) → `401 {"reason":"idle_timeout"}`; selain itu redirect ke `admin.login` (jika user admin) atau `login` dengan `status`: **"Kamu keluar otomatis karena tidak aktif selama 5 menit."** (angka menit = `intdiv(timeout, 60)`).
- Header `X-Background-Request: 1` → tidak memperbarui `last_activity_at`.
- `POST /sesi/ping` → `session.ping` (middleware `auth`) → `204`.
- `destroy`: jika `$request->input('reason') === 'idle'` → redirect ke halaman login yang sesuai dengan pesan `status` yang sama; selain itu tetap ke `/`.

- [ ] **Step 1: Tulis test yang gagal:**

```php
test_user_is_still_logged_in_after_299_seconds()
test_user_is_logged_out_after_301_seconds_with_message()   // assertRedirect(route('login')); assertSessionHas('status', ...)
test_admin_is_redirected_to_admin_login_on_idle()
test_json_request_gets_401_with_reason()
test_background_header_does_not_extend_session()            // request bg di 200s, lalu request biasa di 301s → logout
test_ping_extends_session()                                 // ping di 200s → request di 450s masih login
test_idle_logout_via_logout_form_redirects_to_login_with_message()
test_timeout_is_configurable()                              // config(['auth.idle_timeout' => 60]); travel(61) → logout
```

- [ ] **Step 2:** Jalankan. Expected: FAIL.
- [ ] **Step 3:** Implementasi + tambahkan `AUTH_IDLE_TIMEOUT=300` di `.env.example`. Halaman login wisatawan dan admin sudah menampilkan `session('status')` lewat `x-auth-session-status`; pastikan pesannya muncul.
- [ ] **Step 4:** `php artisan test`. Expected: semua PASS.
- [ ] **Step 5: Commit** — `feat(auth): log out sessions after configurable idle timeout`

---

### Task 9: Idle timeout — browser (Alpine)

**Files:**
- Create: `resources/js/idle-timeout.js`, `resources/views/partials/idle-timeout.blade.php`
- Modify: `resources/js/app.js` (`Alpine.data('idleTimeout', idleTimeout)` sebelum `Alpine.start()`), `resources/views/layouts/app.blade.php`, `resources/views/components/admin-layout.blade.php` (`@auth @include('partials.idle-timeout') @endauth`)
- Test: `tests/Feature/Auth/IdleTimeoutMarkupTest.php` + checklist manual

**Interfaces:**
- Consumes: `session.ping`, `logout`, `config('auth.idle_timeout')` (Task 8).
- Partial: `<div x-data="idleTimeout" data-timeout="..." data-ping-url="..." data-login-url="...">`, modal dengan hitung mundur dan tombol **"Tetap masuk"**, teks **"Sesi akan berakhir dalam :n detik"**, serta form tersembunyi `id="idle-logout-form"` (POST `logout`, `reason=idle`). `data-login-url` = `admin.login` untuk admin, `login` untuk wisatawan.
- `idleTimeout()` (perilaku sesuai spec §5.3): event aktivitas `mousemove keydown pointerdown scroll touchstart` (passive) → `lastActivity = Date.now()` + broadcast `{type:'activity', at}` lewat `BroadcastChannel('jogjatrip-idle')` (fallback `localStorage` key `jogjatrip-idle` + event `storage`); interval 1 detik untuk modal/logout; ping ≤ 1×/60 detik hanya jika ada aktivitas sejak ping terakhir (header `X-CSRF-TOKEN` dari meta); saat logout broadcast `{type:'logout'}`, dan tab penerima melakukan `location = loginUrl`. Bungkus `window.fetch` dan pasang interceptor `axios`: respons 401 dengan `reason === 'idle_timeout'` → `location = loginUrl`.

- [ ] **Step 1: Tulis test yang gagal** (`IdleTimeoutMarkupTest`): halaman `home` sebagai user login memuat `x-data="idleTimeout"` dan `data-timeout="300"`; sebagai tamu tidak memuatnya; halaman admin juga memuatnya dengan `data-login-url` = `route('admin.login')`.
- [ ] **Step 2:** Jalankan. Expected: FAIL.
- [ ] **Step 3:** Implementasi JS + partial; `npm run build` harus sukses.
- [ ] **Step 4:** `php artisan test`. Expected: PASS. Jalankan **checklist manual** spec §8 (6 butir) dengan `AUTH_IDLE_TIMEOUT=120` agar cepat, lalu catat hasilnya di pesan commit/PR.
- [ ] **Step 5: Commit** — `feat(auth): add idle warning modal and cross-tab auto-logout`

---

### Task 10: Profil › Keamanan & banner recovery code

**Files:**
- Create: `app/Http/Controllers/Profile/TwoFactorSecurityController.php`, `resources/views/profile/partials/two-factor-security.blade.php`, `resources/views/partials/recovery-codes-banner.blade.php`
- Modify: `routes/web.php`, `resources/views/profile/edit.blade.php`, `resources/views/layouts/app.blade.php`, `resources/views/components/admin-layout.blade.php`
- Test: `tests/Feature/Profile/TwoFactorSecurityTest.php`

**Interfaces:**
- Consumes: `TwoFactorAuthenticator::verifyUserCode/generateRecoveryCodes`, `TrustedDeviceManager::revoke/revokeAll`, `UserAgentSummary`, route `two-factor.recovery-codes` (Task 5).
- Routes (middleware `auth`):
  - `POST /profile/keamanan/recovery-codes` → `profile.recovery-codes.regenerate` (field `code` wajib valid → simpan hash baru, flash plain ke `two_factor.recovery_codes_plain`, redirect ke `two-factor.recovery-codes`)
  - `DELETE /profile/keamanan/perangkat/{trustedDevice}` → `profile.trusted-devices.destroy` (bukan milik user → 404)
  - `DELETE /profile/keamanan/perangkat` → `profile.trusted-devices.destroy-all`
- Banner muncul jika `auth()->user()->hasTwoFactorEnabled() && recoveryCodesRemaining() <= 2`, dengan link ke `profile.edit`.

- [ ] **Step 1: Tulis test yang gagal:**

```php
test_profile_shows_two_factor_status_and_remaining_codes()
test_regenerate_requires_valid_code()
test_regenerate_replaces_old_codes()                    // kode lama ditolak useRecoveryCode, 8 kode baru
test_profile_lists_trusted_devices_with_summary()       // assertSee('Chrome di Windows')
test_user_can_revoke_own_device()
test_user_cannot_revoke_another_users_device()          // assertNotFound
test_revoke_all_removes_every_device()
test_banner_shows_when_two_or_fewer_codes_remain()
test_banner_hidden_when_more_than_two_codes_remain()
```

- [ ] **Step 2:** Jalankan. Expected: FAIL.
- [ ] **Step 3:** Implementasi (kartu baru di `profile/edit.blade.php` di antara form password dan hapus akun, dengan gaya kartu yang sama).
- [ ] **Step 4:** `php artisan test`. Expected: PASS.
- [ ] **Step 5: Commit** — `feat(2fa): add profile security section and low recovery code banner`

---

### Task 11: Admin › Reset 2FA user

**Files:**
- Modify: `app/Http/Controllers/Admin/UserController.php` (method `resetTwoFactor(Request $request, User $user): RedirectResponse`), `routes/admin.php`, `resources/views/admin/users/show.blade.php`
- Test: `tests/Feature/Admin/ResetTwoFactorTest.php`

**Interfaces:**
- Consumes: `SecureAccountAction::handle` (Task 3), `TwoFactorResetByAdminNotification` (Task 4).
- Route `POST /admin/pengguna/{user}/reset-2fa` → `admin.users.reset-two-factor` (grup `auth`, `admin`). Target = admin sendiri → `403`. Sukses → `back()->with('status', '2FA pengguna berhasil di-reset.')`. Tombol di view memakai `confirm('Reset 2FA pengguna ini? Mereka harus mengaturnya ulang saat login berikutnya.')`.

- [ ] **Step 1: Tulis test yang gagal:** `test_admin_can_reset_another_users_2fa` (2FA kosong, trusted devices 0, sessions dihapus, notifikasi terkirim), `test_admin_cannot_reset_own_2fa` (403), `test_traveler_cannot_access_reset_route`, `test_show_page_has_reset_button`.
- [ ] **Step 2:** Jalankan. Expected: FAIL.
- [ ] **Step 3:** Implementasi.
- [ ] **Step 4:** `php artisan test`. Expected: PASS.
- [ ] **Step 5: Commit** — `feat(admin): allow admins to reset a user's 2FA`

---

### Task 12: Deploy — queue worker & dokumentasi

**Files:**
- Modify: `docker-compose.yml` (service `queue` meniru `scheduler`, dengan `command: ["sh", "-c", "sleep 15 && php artisan queue:work --tries=3 --backoff=30"]`), `.env.example` (`SESSION_SECURE_COOKIE` + komentar `MAIL_*` production)
- Modify (file **belum ter-track milik user**; tanyakan dulu sebelum di-commit): `docker/paas/start.sh` (jalankan `php artisan queue:work --tries=3 --backoff=30 &` dan `php artisan schedule:work &` sebelum nginx), `docs/deployment/PAAS-GUIDE.md`, `docs/deployment/IAAS-GUIDE.md` (poin spec §9)

- [ ] **Step 1:** `docker compose config` → Expected: valid, ada service `queue`.
- [ ] **Step 2:** Tambahkan bagian "2FA & Keamanan" di kedua guide: provider email, queue worker + scheduler, HTTPS + `SESSION_SECURE_COOKIE=true`, `AUTH_IDLE_TIMEOUT=300`, **peringatan jangan mengganti `APP_KEY`**, dan info bahwa user lama akan diminta setup 2FA.
- [ ] **Step 3:** `php artisan test` dan `npm run build`. Expected: semua PASS / build sukses.
- [ ] **Step 4: Commit** file yang ter-track — `chore(deploy): run queue worker and document 2FA deployment requirements`. Untuk `docker/paas/` dan `docs/deployment/`, tanyakan ke user apakah ikut di-commit.
