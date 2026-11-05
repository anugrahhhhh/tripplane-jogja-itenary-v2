# Panduan Deploy JogjaTrip sebagai IaaS

Panduan ini men-deploy JogjaTrip ke sebuah Virtual Machine (IaaS), di mana **kita sendiri** yang menyiapkan OS, web server, PHP, database, dan konfigurasi jaringan — berbeda dengan PaaS (lihat `docs/deployment/PAAS-GUIDE.md`) di mana provider yang mengurus semua itu.

> **Catatan jujur soal definisi.** Secara ketat, IaaS menurut NIST punya 5 karakteristik, salah satunya *broad network access* (diakses lewat internet publik dari provider cloud) dan *measured service* (disewa, dibayar sesuai pakai). VM lokal di bawah ini **tidak** memenuhi itu — infrastrukturnya laptop sendiri, bukan disewa dari provider mana pun. Yang membuatnya tetap relevan untuk belajar: **proses teknisnya identik** dengan IaaS asli — kita yang install OS, web server, runtime, dan database dari nol, tanpa bantuan otomatis dari provider. Untuk presentasi, sebut ini **"simulasi/demonstrasi konsep IaaS secara lokal"**, bukan IaaS murni — lalu jelaskan satu kalimat: di production sebenarnya, VM ini akan ada di data center cloud provider (Azure/Oracle/AWS) dan diakses lewat IP publik.

## Mengapa VM lokal, bukan cloud asli?

Percobaan awal pakai **Azure for Students** terhambat verifikasi MFA (Multi-Factor Authentication) akun kampus yang tidak bisa diselesaikan tanpa akses langsung ke perangkat & akun institusi. Percobaan kedua pakai **Oracle Cloud Always Free** terhambat rate-limit ("Forbidden: number of requests exceeded") yang persisten saat proses sign-up. Karena kedua jalur cloud publik gagal karena kendala administratif di luar kendali teknis, demo ini dialihkan ke **VM lokal via VirtualBox** — yang berhasil dijalankan penuh dan diverifikasi bekerja.

Ini sendiri jadi poin pembelajaran yang valid: **rintangan administratif (verifikasi identitas, rate-limiting, kebijakan institusi) adalah bagian nyata dari bekerja dengan infrastruktur cloud**, bukan cuma soal teknis.

---

## Jalur A — VM Lokal dengan VirtualBox (yang benar-benar dipakai & terverifikasi)

### 0. Siapkan VirtualBox + Ubuntu

1. Install [VirtualBox](https://www.virtualbox.org/wiki/Downloads) (gratis).
2. Download [Ubuntu Server 22.04/24.04 LTS ISO](https://ubuntu.com/download/server).
3. Di VirtualBox: **Machine → New** → beri nama, pilih ISO image yang didownload, OS akan terdeteksi otomatis sebagai Ubuntu.
4. Centang **"Proceed with Unattended Installation"** untuk instalasi otomatis — isi username, password (ingat baik-baik, dipakai untuk SSH & `sudo` nanti), hostname.
5. Alokasikan **2048 MB RAM**, **20-25 GB disk**, 1-2 CPU.
6. Klik **Finish** dan tunggu instalasi Ubuntu selesai otomatis (~10-15 menit), sampai muncul prompt login di jendela VM.

### 1. Aktifkan SSH di dalam VM

Login ke VM (lewat jendela VirtualBox-nya langsung), lalu:

```bash
sudo apt update
sudo apt install -y openssh-server
sudo systemctl enable --now ssh
```

### 2. Setup Port Forwarding (supaya bisa diakses dari host)

VM pakai network **NAT** (default VirtualBox), jadi perlu port forwarding supaya laptop (host) bisa akses VM:

1. Di jendela VM: **Devices → Network → Network Settings...** → expand **Advanced** → **Port Forwarding**.
2. Tambahkan dua rule:

   | Name | Protocol | Host Port | Guest Port |
   |------|----------|-----------|------------|
   | SSH  | TCP      | 2222      | 22         |
   | HTTP | TCP      | 8080      | 80         |

3. **OK** → **OK**.

Sekarang dari **PowerShell di laptop** (bukan di jendela VM):

```powershell
ssh -p 2222 <username>@localhost
```

### 3. Install dependency di dalam VM (lewat SSH)

> **Catatan versi PHP**: JANGAN pakai PPA `ppa:ondrej/php` kalau base image Ubuntu-nya lebih baru dari yang didukung PPA tersebut — akan gagal dengan error 404 "does not have a Release file". Pakai PHP bawaan repo resmi Ubuntu saja (biasanya sudah ≥ 8.2, cukup untuk Laravel 12).

```bash
sudo apt update && sudo apt upgrade -y

# Nginx
sudo apt install -y nginx

# PHP + ekstensi yang dipakai JogjaTrip (dari repo bawaan Ubuntu)
sudo apt install -y php-fpm php-mysql php-mbstring php-xml php-curl php-zip php-gd php-bcmath php-intl
php -v
# pastikan versinya 8.2 atau lebih baru

# MySQL
sudo apt install -y mysql-server

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version

# Node.js + npm (kadang dua paket terpisah)
sudo apt install -y nodejs npm
node --version
npm --version

# Git (biasanya sudah ada bawaan)
sudo apt install -y git
```

### 4. Siapkan database

```bash
sudo mysql
```

```sql
CREATE DATABASE jogjatrip;
CREATE USER 'jogjatrip'@'localhost' IDENTIFIED BY 'GANTI_PASSWORD_INI';
GRANT ALL PRIVILEGES ON jogjatrip.* TO 'jogjatrip'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 5. Clone dan build aplikasi

```bash
cd /var/www
sudo git clone https://github.com/anugrahhhhh/tripplane-jogja-itenary.git jogjatrip
cd jogjatrip
sudo chown -R $USER:$USER /var/www/jogjatrip

composer install --no-dev --optimize-autoloader
npm ci
npm run build

cp .env.example .env
php artisan key:generate
```

Edit `.env` (pakai `nano .env`, atau `sed` kalau lebih nyaman — lihat contoh di bawah):

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost:8080

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=jogjatrip
DB_USERNAME=jogjatrip
DB_PASSWORD=GANTI_PASSWORD_INI
```

> Karena ini VM lokal yang diakses lewat port forwarding `8080→80`, `APP_URL` pakai `http://localhost:8080` — bukan IP publik seperti pada VM cloud asli (lihat Jalur B di bawah).

Lalu migrasi dan seeding:

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link

sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 6. Konfigurasi Nginx

```bash
sudo tee /etc/nginx/sites-available/jogjatrip > /dev/null <<'EOF'
server {
    listen 80;
    server_name _;
    root /var/www/jogjatrip/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

sudo ln -s /etc/nginx/sites-available/jogjatrip /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
```

> **Cek dulu nama socket PHP-FPM yang sebenarnya** sebelum restart (`ls /run/php/`) — bisa beda-beda tergantung versi PHP yang terinstall (misal `php8.5-fpm.sock`). Sesuaikan baris `fastcgi_pass` di atas kalau perlu, baru jalankan:

```bash
sudo nginx -t && sudo systemctl restart nginx
```

### 7. Jadwalkan scheduler (pengingat itinerary H-1)

```bash
crontab -e
```

Tambahkan baris (urutan di file tidak masalah, baris `#` adalah komentar):

```
* * * * * cd /var/www/jogjatrip && php artisan schedule:run >> /dev/null 2>&1
```

Simpan dengan **Ctrl+O** → **Enter** → **Ctrl+X**. Verifikasi: `crontab -l`.

### 8. Verifikasi

Dari browser di laptop (host): buka **http://localhost:8080** — katalog destinasi, login, dan halaman detail harus berfungsi.

---

## Jalur B — Cloud Provider Asli (Azure / Oracle Cloud)

Panduan ini tetap berguna sebagai referensi, atau kalau ingin dicoba lagi di luar sesi demo (misalnya sign-up sudah tidak terblokir). Langkah-langkahnya sama persis dengan Jalur A di atas, hanya beda di bagian ini:

### Buat VM di cloud

**Azure for Students** ([azure.microsoft.com/free/students](https://azure.microsoft.com/free/students), kredit $100, email kampus `.ac.id`):
1. Create a resource → Virtual Machine → Ubuntu 22.04 LTS, size B1s/B2s.
2. Authentication: SSH key atau password.
3. Networking: buka inbound port **22** (SSH) dan **80** (HTTP).
4. Catat **Public IP**, lalu `ssh azureuser@<IP_PUBLIK_VM>`.

**Oracle Cloud Always Free** ([oracle.com/cloud/free](https://www.oracle.com/cloud/free/), gratis selamanya, perlu kartu untuk verifikasi identitas tanpa ditagih):
1. Pilih Home Region **Singapore** atau **Jakarta** (tidak bisa diubah setelah dibuat).
2. Compute → Create Instance → image **Ubuntu**, shape **Always Free eligible** (`VM.Standard.A1.Flex` atau `VM.Standard.E2.1.Micro`).
3. Buka port 80 di **dua tempat**: Security List VCN (Networking → Virtual Cloud Networks → Security Lists → Add Ingress Rule, port 80) **dan** iptables di dalam VM (`sudo iptables -I INPUT -p tcp --dport 80 -j ACCEPT`).
4. Username default **`ubuntu`**, bukan `azureuser`.

### Perbedaan dari Jalur A

- `APP_URL` dan `GOOGLE_REDIRECT_URI` di `.env` pakai **IP publik VM** (`http://<IP_PUBLIK_VM>`), bukan `http://localhost:8080`.
- Tidak perlu port forwarding — VM sudah langsung punya IP yang diakses lewat internet.
- Google OAuth: tambahkan `http://<IP_PUBLIK_VM>/auth/google/callback` ke *Authorized redirect URIs* di [Google Cloud Console](https://console.cloud.google.com/apis/credentials).
- **Matikan/hapus VM setelah selesai** supaya kredit tidak terus terpakai.

---

## 2FA & Keamanan

Fitur 2FA (TOTP) wajib untuk semua user. Hal-hal berikut harus dipenuhi saat deploy:

- **Provider email.** Notifikasi keamanan (2FA aktif/nonaktif, perangkat baru, dll.) dikirim lewat email. Isi `MAIL_MAILER` (SMTP/Resend/Mailgun) beserta kredensialnya (`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, dst.), dan pakai `MAIL_FROM_ADDRESS` dengan domain sendiri. Default `MAIL_MAILER=log` hanya menulis ke log.
- **Queue worker harus jalan.** Keempat notifikasi keamanan masuk antrean (`QUEUE_CONNECTION=database`): `php artisan queue:work --tries=3 --backoff=30`. Tanpa worker, email tidak pernah terkirim.
- **Scheduler harus jalan.** Dipakai untuk `trusted-devices:prune` harian (membersihkan perangkat tepercaya kedaluwarsa), selain pengingat itinerary.
- **HTTPS wajib** dan set `SESSION_SECURE_COOKIE=true` (cookie perangkat tepercaya ikut flag Secure ini).
- **`SESSION_DRIVER=database` jangan diubah.** Alur "Bukan saya" dan Reset 2FA oleh admin mengeluarkan user dengan menghapus baris di tabel `sessions`; dengan driver `file`/`redis` fitur itu tidak berefek.
- **`APP_LOCALE=id`** supaya tanggal di email memakai nama bulan Indonesia.
- **`AUTH_IDLE_TIMEOUT=120`** (detik) untuk batas idle sebelum user dikeluarkan.
- **JANGAN mengganti `APP_KEY`** setelah ada user yang setup 2FA. Secret TOTP dienkripsi dengan key ini; kalau berubah, secret tidak bisa dibaca lagi dan user terkunci. Pastikan `.env.docker` berisi `APP_KEY` tetap (sudah di-generate sekali): service `queue` dan `scheduler` memakai `entrypoint: []` sehingga tidak men-generate key dan bergantung penuh pada nilai ini.
- **Migrasi:** `php artisan migrate --force` menambah kolom 2FA, tabel `trusted_devices`, dan membersihkan semua `remember_token` — cookie "Ingat saya" lama berhenti berlaku sekali (memang disengaja).
- **Build frontend:** `npm run build` harus dijalankan (ada JS baru untuk modal idle-timeout).
- **User lama** (termasuk yang login via Google) akan diminta setup 2FA pada login berikutnya setelah deploy.

Di VM (IaaS), queue worker perlu dijalankan sebagai service supaya tetap hidup dan otomatis start ulang, misalnya dengan systemd (`/etc/systemd/system/jogjatrip-queue.service`):

```ini
[Unit]
Description=JogjaTrip queue worker
After=network.target mysql.service

[Service]
User=www-data
WorkingDirectory=/var/www/jogjatrip
ExecStart=/usr/bin/php artisan queue:work --tries=3 --backoff=30
Restart=always

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now jogjatrip-queue
```

Scheduler sudah ditangani cron `schedule:run` di langkah 7. Setelah deploy ulang kode, jalankan `php artisan queue:restart` supaya worker memuat kode baru. Untuk HTTPS di VM, pasang sertifikat (mis. Let's Encrypt/certbot) dan set `APP_URL=https://...` serta `SESSION_SECURE_COOKIE=true` di `.env`.

---

## Catatan untuk perbandingan di presentasi

Dibanding PaaS (`docs/deployment/PAAS-GUIDE.md`), di jalur IaaS ini **kita** yang mengurus: pemilihan OS & versi PHP, instalasi tiap komponen (Nginx, PHP-FPM, MySQL), konfigurasi web server, keamanan firewall, dan penjadwalan cron — bukan provider. Ini poin utama untuk tabel "siapa mengurus apa" di slide. Kendala sign-up Azure/Oracle yang dialami juga poin diskusi yang baik: menunjukkan bahwa "gratis" di cloud sering tetap punya gesekan administratif nyata (verifikasi identitas, kebijakan institusi, rate-limiting anti-fraud).
