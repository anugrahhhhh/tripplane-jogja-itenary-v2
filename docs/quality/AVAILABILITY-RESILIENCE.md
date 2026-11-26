# Availability & Resilience — JogjaTrip

Bagian Anugrah dari [ANALISIS-KUALITAS.md](../ANALISIS-KUALITAS.md).

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
