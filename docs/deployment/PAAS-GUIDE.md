# Panduan Deploy JogjaTrip sebagai PaaS (Railway)

Berbeda dengan IaaS (`docs/deployment/IAAS-GUIDE.md`), di PaaS kita **tidak** menyiapkan OS, web server, atau database sendiri — provider yang mengurus semua itu. Kita hanya menyediakan kode (lewat Dockerfile) dan environment variable.

> **Catatan arsitektur**: Dockerfile utama di root repo (`Dockerfile`) dipakai untuk docker-compose, di mana Nginx dan PHP-FPM berjalan sebagai dua container terpisah. Kebanyakan PaaS seperti Railway hanya menjalankan **satu container per service**, jadi dibuat Dockerfile khusus di `docker/paas/Dockerfile` yang menjalankan Nginx + PHP-FPM dalam satu container yang sama. Sudah dicoba jalan secara lokal (build + run, lengkap dengan migrasi & seeding) sebelum dipakai di Railway.

## Langkah di Railway

1. Buat akun di [railway.app](https://railway.app) (bisa login dengan GitHub).
2. **New Project → Deploy from GitHub repo** → pilih repo `tripplane-jogja-itenary`.
   Railway otomatis membaca `railway.json` di root repo, yang mengarahkan build ke `docker/paas/Dockerfile`.
3. **Add a service → Database → MySQL** di project yang sama. Railway akan membuat variabel seperti `MYSQLHOST`, `MYSQLPORT`, `MYSQLDATABASE`, `MYSQLUSER`, `MYSQLPASSWORD`.
4. Buka service aplikasi → tab **Variables**, isi:

   ```
   APP_NAME=JogjaTrip
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY=base64:...            # WAJIB: generate sekali di lokal (php artisan key:generate --show), tempel di sini, jangan pernah diganti
   APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}   # wajib diawali https:// (link di email keamanan memakai APP_URL)

   DB_CONNECTION=mysql
   DB_HOST=${{MySQL.MYSQLHOST}}
   DB_PORT=${{MySQL.MYSQLPORT}}
   DB_DATABASE=${{MySQL.MYSQLDATABASE}}
   DB_USERNAME=${{MySQL.MYSQLUSER}}
   DB_PASSWORD=${{MySQL.MYSQLPASSWORD}}

   SESSION_DRIVER=database
   CACHE_STORE=database
   QUEUE_CONNECTION=database

   GEMINI_API_KEY=isi_api_key_milikmu
   GEMINI_MODEL=gemini-3.6-flash
   GEMINI_FALLBACK_MODELS=gemini-3.5-flash,gemini-3.1-flash-lite

   GOOGLE_CLIENT_ID=isi_client_id_milikmu
   GOOGLE_CLIENT_SECRET=isi_client_secret_milikmu
   GOOGLE_REDIRECT_URI=https://${{RAILWAY_PUBLIC_DOMAIN}}/auth/google/callback
   ```

   `${{MySQL.MYSQLHOST}}` dkk adalah sintaks referensi variabel Railway — ambil nilai dari service MySQL yang baru dibuat. `${{RAILWAY_PUBLIC_DOMAIN}}` otomatis terisi domain publik yang Railway berikan.

5. Railway otomatis mem-build image dari `docker/paas/Dockerfile` lalu menjalankannya. Container akan:
   - berhenti dengan error jika `APP_KEY` kosong di production (tidak digenerate otomatis, karena secret 2FA terenkripsi dengan key ini),
   - menunggu MySQL siap,
   - menjalankan `migrate` + `db:seed`,
   - menjalankan PHP-FPM + Nginx pada port yang diberikan Railway lewat variabel `PORT` (otomatis, tidak perlu diisi manual).
6. **Penting — Google OAuth**: setelah domain publik Railway muncul, tambahkan `<domain>/auth/google/callback` ke *Authorized redirect URIs* di [Google Cloud Console](https://console.cloud.google.com/apis/credentials). Tanpa ini, login Google gagal.

## Trade-off yang perlu dibahas di presentasi

- **Storage foto tidak permanen.** Foto destinasi yang diupload admin disimpan di `storage/app/public` di dalam container. Setiap kali Railway rebuild/redeploy, container lama dibuang dan container baru dibuat dari image — foto yang diupload sebelumnya **hilang**. Ini adalah contoh nyata keterbatasan PaaS: filesystem container bersifat *ephemeral*, berbeda dengan VM (IaaS) yang disknya persisten selama VM tidak dihapus. Solusi produksi aslinya adalah pindah ke object storage (S3-compatible), tapi itu di luar scope demo ini — cukup disebutkan sebagai temuan.
- **Scheduler jalan di dalam container, bukan crontab sendiri.** Di IaaS kita pasang cron manual untuk memanggil `php artisan schedule:run` tiap menit. Di Railway, `docker/paas/start.sh` menjalankan `php artisan schedule:work` (dan queue worker) sebagai proses background di container yang sama, dibungkus loop restart otomatis dan berjalan sebagai `www-data`. Untuk beban yang lebih berat, scheduler/worker bisa dipisah menjadi service Railway tersendiri (mirip service `scheduler` dan `queue` di `docker-compose.yml`). Intinya: kita tidak mengatur cron OS sendiri, tapi menyesuaikan ke mekanisme yang disediakan provider.
- **Kita tidak pernah menyentuh OS.** Tidak ada SSH, tidak ada `apt install`, tidak ada firewall untuk diatur — ini poin utama untuk tabel "siapa mengurus apa" dibandingkan IaaS.

## 2FA & Keamanan

Fitur 2FA (TOTP) wajib untuk semua user. Hal-hal berikut harus dipenuhi saat deploy:

- **Provider email.** Notifikasi keamanan (2FA aktif/nonaktif, perangkat baru, dll.) dikirim lewat email. Isi `MAIL_MAILER` (SMTP/Resend/Mailgun) beserta kredensialnya (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, dst.), dan pakai `MAIL_FROM_ADDRESS` dengan domain sendiri. Default `MAIL_MAILER=log` hanya menulis ke log.
- **Queue worker harus jalan.** Keempat notifikasi keamanan masuk antrean (`QUEUE_CONNECTION=database`): `php artisan queue:work --tries=3 --backoff=30`. Tanpa worker, email tidak pernah terkirim.
- **Scheduler harus jalan.** Dipakai untuk `trusted-devices:prune` harian (membersihkan perangkat tepercaya kedaluwarsa), selain pengingat itinerary.
- **HTTPS wajib** dan set `SESSION_SECURE_COOKIE=true` (cookie perangkat tepercaya ikut flag Secure ini).
- **`SESSION_DRIVER=database` jangan diubah.** Alur "Bukan saya" dan Reset 2FA oleh admin mengeluarkan user dengan menghapus baris di tabel `sessions`; dengan driver `file`/`redis` fitur itu tidak berefek.
- **`APP_LOCALE=id`** supaya tanggal di email memakai nama bulan Indonesia.
- **`AUTH_IDLE_TIMEOUT=300`** (detik) untuk batas idle sebelum user dikeluarkan.
- **JANGAN mengganti `APP_KEY`** setelah ada user yang setup 2FA. Secret TOTP dienkripsi dengan key ini; kalau berubah, secret tidak bisa dibaca lagi dan user terkunci.
- **Migrasi:** `php artisan migrate --force` menambah kolom 2FA, tabel `trusted_devices`, dan membersihkan semua `remember_token` — cookie "Ingat saya" lama berhenti berlaku sekali (memang disengaja).
- **Build frontend:** `npm run build` harus dijalankan (ada JS baru untuk modal idle-timeout).
- **User lama** (termasuk yang login via Google) akan diminta setup 2FA pada login berikutnya setelah deploy.

Di PaaS ini (`docker/paas/start.sh`), queue worker dan scheduler dijalankan sebagai proses background di container yang sama (auto-restart, user `www-data`), sebelum Nginx. Tambahkan variabel berikut di tab **Variables** Railway:

```
SESSION_SECURE_COOKIE=true
APP_LOCALE=id
AUTH_IDLE_TIMEOUT=300
MAIL_MAILER=smtp            # atau resend / mailgun
MAIL_HOST=isi_host_provider
MAIL_PORT=587
MAIL_SCHEME=smtp            # smtp (STARTTLS, port 587) atau smtps (port 465)
MAIL_USERNAME=isi_username
MAIL_PASSWORD=isi_password
MAIL_FROM_ADDRESS=noreply@domainmu.com
```
