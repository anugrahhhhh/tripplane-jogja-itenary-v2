# JogjaTrip

JogjaTrip adalah aplikasi perencana perjalanan wisata untuk Kota Yogyakarta, dibangun dengan Laravel. Aplikasi ini membantu wisatawan menjelajahi destinasi, menyusun itinerary multi-hari, dan mendapatkan rekomendasi personalisasi berbasis AI, sekaligus menyediakan panel admin untuk mengelola data destinasi, kategori, dan ulasan.

## Fitur Utama

- **Katalog destinasi** — 72 destinasi di Kota Yogyakarta, dikelompokkan per kategori, lengkap dengan jam operasional, rentang harga, dan ulasan pengguna.
- **Itinerary builder** — susun rencana perjalanan multi-hari, urutkan otomatis berdasarkan jarak terdekat, dan ekspor ke PDF.
- **Rekomendasi AI** — rekomendasi destinasi personalisasi dan generator itinerary otomatis menggunakan Google Gemini API.
- **Cuaca** — prakiraan cuaca per hari untuk tanggal itinerary (Open-Meteo).
- **Kolaborasi itinerary** — undang pengguna lain untuk melihat/mengelola itinerary bersama.
- **Notifikasi** — pengingat itinerary via email dan notifikasi dalam aplikasi, dijalankan terjadwal.
- **PWA** — dapat dipasang (installable) dan tetap dapat diakses dalam kondisi offline terbatas.
- **Login Google (OAuth)** — untuk wisatawan, terpisah dari akses login admin.
- **Dashboard admin** — statistik dan visualisasi (Chart.js) destinasi favorit serta tren itinerary.

## Tech Stack

- Laravel 12, PHP 8.2
- MySQL 8
- Tailwind CSS + Alpine.js (Vite)
- Google Gemini API, Open-Meteo API
- PHPUnit (automated test suite)

## Menjalankan dengan Docker (disarankan)

Cara tercepat menjalankan JogjaTrip secara lengkap (aplikasi, web server, database, dan scheduler) tanpa instalasi manual PHP/MySQL.

### Prasyarat

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (atau Docker Engine + Compose plugin) sudah terpasang dan berjalan.

### Langkah

1. Salin berkas environment Docker, lalu isi API key yang kamu miliki:

   ```bash
   cp .env.docker.example .env.docker
   ```

   Edit `.env.docker` dan isi minimal:
   - `GEMINI_API_KEY` — untuk fitur rekomendasi & itinerary AI ([ambil di sini](https://aistudio.google.com/apikey)).
   - `GOOGLE_CLIENT_ID` / `GOOGLE_CLIENT_SECRET` — jika ingin mengaktifkan login Google ([Google Cloud Console](https://console.cloud.google.com/apis/credentials)).

   Aplikasi tetap bisa berjalan tanpa kedua key tersebut; hanya fitur AI dan login Google yang tidak aktif.

2. Build dan jalankan seluruh service:

   ```bash
   docker compose up -d --build
   ```

   Saat container `app` pertama kali start, secara otomatis akan: generate `APP_KEY` (jika belum ada), menjalankan migrasi database, dan mengisi data awal (kategori, 72 destinasi, serta ulasan contoh).

3. Buka aplikasi di [http://localhost:8000](http://localhost:8000).

4. Untuk menghentikan:

   ```bash
   docker compose down
   ```

   Data database tersimpan di volume `db_data` dan tetap ada walau container dihentikan. Gunakan `docker compose down -v` jika ingin menghapus data juga.

### Arsitektur Docker

| Service     | Deskripsi                                                          |
|-------------|---------------------------------------------------------------------|
| `nginx`     | Web server, melayani request di port `8000` (host) → `80` (container) |
| `app`       | PHP-FPM, menjalankan kode Laravel                                   |
| `scheduler` | Menjalankan `php artisan schedule:work` (pengingat itinerary harian) |
| `db`        | MySQL 8                                                              |

## Menjalankan secara lokal (tanpa Docker)

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
# sesuaikan koneksi database & API key di .env
php artisan migrate --seed
php artisan serve
```

## Menjalankan test

```bash
php artisan test
```

## Lisensi

Proyek ini dibangun di atas framework [Laravel](https://laravel.com), yang bersifat open-source dengan lisensi MIT.

## Tim Pengembang

| Nama | GitHub |
|---|---|
| Anugrah Putra | [@anugrahhhhh](https://github.com/anugrahhhhh) |
| Venessa Yumadila | [@nessyahra](https://github.com/nessyahra) |
