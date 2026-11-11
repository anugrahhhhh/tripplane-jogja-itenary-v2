# Availability & Resilience — Panduan Operasional

Dokumen ini menjelaskan bagaimana JogjaTrip tetap tersedia (availability) dan tetap berfungsi atau gagal dengan anggun saat ada gangguan (resilience), serta langkah pemulihan bila terjadi masalah.

## 1. Topologi (docker-compose)

```
            ┌──► app1 ─┐                       ┌──► db (MySQL primary: tulis + baca)
 nginx ─────┤          ├──► redis (cache) ─────┤        │ replikasi GTID
            └──► app2 ─┘                       └──► db-replica (baca)
 queue · queue-ai · scheduler ─────────────────────────┘
```

| Komponen | Bila mati | Dampak ke pengguna |
|---|---|---|
| app1 / app2 | Nginx mengalihkan ke instance lain (`fastcgi_next_upstream`, `max_fails`) | Tidak terasa |
| Semua app | Nginx menampilkan halaman statis `fallback.html` (auto-refresh 20 dtk) | Halaman "sedang dipulihkan", bukan "502 Bad Gateway" |
| redis | Cache otomatis pindah ke database (`CACHE_STORE=failover`) | Sedikit lebih lambat |
| db-replica | Laravel membaca dari host berikutnya di `DB_READ_HOST` (primary) | Tidak terasa |
| db (primary) | Halaman 503 "gangguan sementara" + `Retry-After`, auto-refresh 30 dtk | Tidak bisa dipakai sampai pulih |
| queue worker | Permintaan AI > 2 menit ditandai gagal, tombol "Coba lagi" | AI tertunda |
| scheduler | `/health` = degraded, peringatan otomatis | Backup/pengingat tertunda |

Mulai dari nol: `docker compose up -d --build`. Service `migrate` menjalankan migrasi sekali, `db-replica-setup` membangun replikasi (idempoten), lalu app1/app2 start.

## 2. Mekanisme resilience

| Mekanisme | Di mana | Fungsi |
|---|---|---|
| Circuit breaker | `App\Support\CircuitBreaker` (Gemini, cuaca) | Setelah N gagal beruntun, layanan "diistirahatkan" sehingga pengguna langsung mendapat pesan jelas, bukan menunggu timeout. Setelah cooldown, 1 percobaan (half-open). |
| Stale-if-error | `WeatherService` | Open-Meteo gangguan → prakiraan terakhir yang berhasil dipakai (hingga 72 jam). Kegagalan hanya di-cache 2 menit. |
| Fallback model AI | `CallsGeminiApi` | Model utama → model cadangan, dengan batas waktu total. |
| Retry transaksi | `App\Support\Transaction` | Deadlock diulang otomatis (default 3x). |
| Retry notifikasi | `RetriesDelivery` | Email dicoba 5x dengan jeda 30 dtk → 2 mnt → 10 mnt → 30 mnt. |
| Isolasi pengingat | `app:send-itinerary-reminders` | Satu itinerary gagal tidak menghentikan yang lain; dicoba ulang tiap jam 08:00–20:00. |
| Mail failover | `MAIL_MAILER=failover` | SMTP utama gagal → mailer berikutnya di `MAIL_FAILOVER_MAILERS`. |
| DB outage → 503 | `App\Support\DatabaseOutage` | 503 + `Retry-After`, log dibatasi 5/menit (tidak banjir). |
| Read-your-writes | `ReadYourWrites` middleware | Setelah menyimpan, request berikutnya membaca dari primary 5 detik, supaya data baru tidak "hilang" karena replika tertinggal. |
| Bulkhead | worker `queue-ai` terpisah | AI yang lambat tidak menahan email/notifikasi. |

Semua angka bisa diatur lewat `.env` (`BREAKER_*`, `DB_TRANSACTION_ATTEMPTS`, `WEATHER_STALE_HOURS`), lihat `config/resilience.php`.

## 3. Pemantauan & peringatan

- `GET /up`: liveness (PHP hidup).
- `GET /health`: readiness (database, replika, cache, storage, antrean, worker, scheduler, backup, circuit breaker). Kode 503 hanya bila komponen inti mati. Detail per komponen: header `X-Health-Token: <HEALTH_TOKEN>`.
- Dashboard Admin → **Kesehatan Sistem**.
- `php artisan app:health --notify` dijadwalkan tiap 5 menit. Peringatan dikirim saat status memburuk, diulang setiap `HEALTH_ALERT_COOLDOWN` menit selama belum pulih, dan sekali saat pulih. Penerima: email `HEALTH_ALERT_EMAIL` (dikirim langsung, tidak lewat antrean), notifikasi in-app semua admin, dan log.

## 4. Backup

- Harian pukul 02:00: dump → **verifikasi** (gzip utuh + dump lengkap / `PRAGMA integrity_check`) → ditulis ke semua disk di `BACKUP_DISKS` beserta file `.sha256` → ukuran dicocokkan.
- Satu disk gagal tidak membatalkan disk lain (perintah keluar dengan kode gagal supaya terpantau).
- Backup lebih tua dari `BACKUP_KEEP_DAYS` dihapus, tetapi backup terbaru **selalu** disisakan.
- Disk `offsite` = folder `BACKUP_OFFSITE_ROOT`. Di produksi, mount ke penyimpanan di luar server (NFS, disk terpisah). Untuk S3: `composer require league/flysystem-aws-s3-v3` lalu tambahkan `s3` ke `BACKUP_DISKS`.

## 5. Pemulihan (restore)

```bash
php artisan app:restore-database --list                 # daftar backup
php artisan app:restore-database                        # restore backup terbaru (dengan konfirmasi)
php artisan app:restore-database --disk=offsite         # dari salinan luar server
php artisan app:restore-database backups/jogjatrip-20261105-020000.sql.gz
```

Langkah otomatis: verifikasi checksum & isi (backup rusak ditolak) → backup pengaman kondisi saat ini → mode perawatan (`down`) → restore → cache katalog dibersihkan → aplikasi dibuka (`up`).

Di Docker: `docker compose exec app1 php artisan app:restore-database`. Mode perawatan berlaku untuk semua instance (`APP_MAINTENANCE_DRIVER=cache`). Setelah restore MySQL, bangun ulang replika: `docker compose run --rm db-replica-setup`.

Uji restore ke database terpisah minimal sebulan sekali.

## 6. Primary database mati

1. Peringatan "GANGGUAN" masuk; pengguna melihat halaman 503 yang auto-refresh.
2. Bila primary bisa dihidupkan lagi (`docker compose restart db`), replika menyambung sendiri (`SOURCE_RETRY_COUNT`).
3. Bila data primary rusak: `app:restore-database --disk=offsite`, lalu `docker compose run --rm db-replica-setup`.

Promosi replika menjadi primary otomatis (automatic failover) memerlukan orchestrator (mis. MySQL InnoDB Cluster/Orchestrator) atau database terkelola (RDS Multi-AZ, Cloud SQL HA). Untuk produksi sungguhan, database terkelola adalah pilihan yang disarankan.

## 7. PaaS (Railway)

- `restartPolicyType: ALWAYS`: container yang crash selalu dihidupkan lagi.
- Migrasi memakai `--isolated`, dan semua jadwal memakai `onOneServer()`, jadi aman bila `numReplicas` dinaikkan. Syaratnya: upload foto harus pindah ke object storage (S3) lebih dulu, karena disk container tidak dibagi antar replika.
- Nginx di dalam container menampilkan `fallback.html` saat PHP-FPM restart.

## 8. Checklist produksi

- [ ] `APP_KEY` sama di semua instance (wajib, sudah dicek entrypoint di production)
- [ ] `HEALTH_TOKEN` diisi, uptime monitor eksternal memantau `/health`
- [ ] `HEALTH_ALERT_EMAIL` diisi dan email ujinya sampai
- [ ] `BACKUP_DISKS` berisi minimal satu lokasi di luar server
- [ ] Restore pernah diuji
- [ ] Kartu Kesehatan Sistem: semua hijau (worker, scheduler, replika)
