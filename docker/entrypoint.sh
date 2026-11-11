#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
    if [ -f .env.docker.example ]; then
        echo "[entrypoint] .env belum ada, menyalin dari .env.docker.example..."
        cp .env.docker.example .env
    else
        echo "[entrypoint] .env belum ada dan .env.docker.example tidak ditemukan, membatalkan."
        exit 1
    fi
fi

if [ -z "$APP_KEY" ] && ! grep -q "^APP_KEY=base64:" .env; then
    if [ "$APP_ENV" = "production" ]; then
        echo "[entrypoint] APP_KEY wajib diisi di production; jangan generate otomatis karena secret 2FA terenkripsi dengan kunci ini."
        exit 1
    fi
    echo "[entrypoint] APP_KEY belum diisi, generate baru..."
    php artisan key:generate --force
fi

php artisan config:clear

echo "[entrypoint] Menunggu database siap menerima koneksi..."
tries=0
until php artisan db:show --quiet > /dev/null 2>&1; do
    tries=$((tries + 1))
    if [ "$tries" -ge 30 ]; then
        echo "[entrypoint] Database tidak merespons setelah 30 percobaan, membatalkan."
        exit 1
    fi
    sleep 2
done

# Dengan banyak instance, hanya satu yang perlu menjalankan migrasi
# (docker-compose: service "migrate"; instance lain RUN_MIGRATIONS=false).
# --isolated mengunci lewat cache supaya dua instance yang kebetulan start
# bersamaan (mis. replika PaaS) tidak menjalankan migrasi bersamaan.
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "[entrypoint] Menjalankan migrasi database..."
    # Fallback tanpa --isolated: pada database yang masih kosong tabel lock
    # cache (cache_locks) belum ada, jadi kunci belum bisa dipakai.
    php artisan migrate --force --isolated || php artisan migrate --force

    echo "[entrypoint] Mengisi data awal (kategori, destinasi, ulasan contoh)..."
    php artisan db:seed --force
else
    echo "[entrypoint] RUN_MIGRATIONS=false: migrasi dilewati (dijalankan instance lain)."
fi

php artisan storage:link --force || true

# Perintah artisan di atas berjalan sebagai root; kalau sempat menulis log,
# file-nya jadi milik root dan PHP-FPM (www-data) gagal menulis log berikutnya.
chown -R www-data:www-data storage/logs storage/framework 2>/dev/null || true

echo "[entrypoint] Siap. Menjalankan: $*"
exec "$@"
