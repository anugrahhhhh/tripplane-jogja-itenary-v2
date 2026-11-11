#!/bin/sh
set -e

PORT="${PORT:-8080}"
echo "[start] Menyiapkan Nginx di port $PORT..."
sed "s/__PORT__/$PORT/" /etc/nginx/conf.d/app.conf.template > /etc/nginx/conf.d/default.conf

echo "[start] Menjalankan PHP-FPM (background)..."
php-fpm -D

# Worker dijalankan sebagai www-data (sama dengan PHP-FPM) supaya storage/logs tidak
# menjadi milik root, dan dibungkus loop agar otomatis restart bila berhenti/crash.
echo "[start] Menjalankan queue worker (background, auto-restart)..."
(
  while true; do
    su -s /bin/sh www-data -c "php artisan queue:work --queue=default,ai --tries=3 --backoff=30 --max-time=3600" || true
    echo "[start] queue worker berhenti, restart dalam 3 detik..."
    sleep 3
  done
) &

echo "[start] Menjalankan scheduler (background, auto-restart)..."
(
  while true; do
    su -s /bin/sh www-data -c "php artisan schedule:work" || true
    echo "[start] scheduler berhenti, restart dalam 3 detik..."
    sleep 3
  done
) &

echo "[start] Menjalankan Nginx (foreground)..."
exec nginx -g 'daemon off;'
