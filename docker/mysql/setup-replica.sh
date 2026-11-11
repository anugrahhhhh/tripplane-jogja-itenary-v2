#!/bin/sh
# Menyiapkan replikasi MySQL primary (db) -> replica (db-replica) berbasis GTID.
#
# Idempoten: aman dijalankan berulang. Bila replikasi sudah berjalan sehat,
# skrip langsung selesai. Bila belum/rusak, replica dibangun ulang dari
# snapshot primary lalu mengejar sisanya lewat binlog (SOURCE_AUTO_POSITION).
#
# User aplikasi di replica (hanya SELECT) dibuat PALING AKHIR. Sebelum itu,
# koneksi aplikasi ke replica ditolak, sehingga Laravel otomatis membaca dari
# primary (tidak pernah membaca replica yang masih kosong).
set -eu

: "${MYSQL_ROOT_PASSWORD:?wajib}"
: "${DB_DATABASE:?wajib}"
: "${DB_USERNAME:?wajib}"
: "${DB_PASSWORD:?wajib}"
: "${REPLICATION_PASSWORD:?wajib}"

export MYSQL_PWD="$MYSQL_ROOT_PASSWORD"
PRIMARY="mysql -h db -uroot --protocol=tcp"
REPLICA="mysql -h db-replica -uroot --protocol=tcp"

wait_for() {
    tries=0
    until mysqladmin ping -h "$1" -uroot --protocol=tcp --silent >/dev/null 2>&1; do
        tries=$((tries + 1))
        [ "$tries" -ge 60 ] && { echo "[replica] $1 tidak merespons"; exit 1; }
        sleep 2
    done
}

replica_healthy() {
    status=$($REPLICA -e "SHOW REPLICA STATUS\G" 2>/dev/null || true)
    echo "$status" | grep -q "Replica_IO_Running: Yes" && echo "$status" | grep -q "Replica_SQL_Running: Yes"
}

wait_for db
wait_for db-replica

if replica_healthy; then
    echo "[replica] Replikasi sudah berjalan sehat, tidak ada yang perlu dilakukan."
    exit 0
fi

echo "[replica] Menyiapkan user replikasi di primary..."
$PRIMARY -e "CREATE USER IF NOT EXISTS 'repl'@'%' IDENTIFIED WITH caching_sha2_password BY '${REPLICATION_PASSWORD}';
             GRANT REPLICATION SLAVE ON *.* TO 'repl'@'%';"

echo "[replica] Membersihkan replica (aplikasi sementara membaca dari primary)..."
# STOP/RESET bisa error bila replica belum pernah dikonfigurasi: abaikan.
$REPLICA -e "STOP REPLICA;" 2>/dev/null || true
$REPLICA -e "RESET REPLICA ALL;" 2>/dev/null || true
$REPLICA -e "SET SESSION sql_log_bin = 0;
             DROP USER IF EXISTS '${DB_USERNAME}'@'%';
             DROP DATABASE IF EXISTS \`${DB_DATABASE}\`;
             SET SESSION sql_log_bin = 1;
             RESET MASTER;"

echo "[replica] Menyalin snapshot konsisten dari primary..."
mysqldump -h db -uroot --protocol=tcp \
    --single-transaction --set-gtid-purged=ON --triggers --routines --events \
    --databases "$DB_DATABASE" | $REPLICA

echo "[replica] Menyambungkan replikasi (GTID auto-position)..."
$REPLICA -e "CHANGE REPLICATION SOURCE TO
                 SOURCE_HOST='db', SOURCE_PORT=3306,
                 SOURCE_USER='repl', SOURCE_PASSWORD='${REPLICATION_PASSWORD}',
                 SOURCE_AUTO_POSITION=1, GET_SOURCE_PUBLIC_KEY=1,
                 SOURCE_CONNECT_RETRY=10, SOURCE_RETRY_COUNT=8640;
             START REPLICA;"

tries=0
until replica_healthy; do
    tries=$((tries + 1))
    if [ "$tries" -ge 30 ]; then
        echo "[replica] Replikasi gagal berjalan:"
        $REPLICA -e "SHOW REPLICA STATUS\G" | grep -E "Running|Error" || true
        exit 1
    fi
    sleep 2
done

echo "[replica] Membuka akses baca untuk aplikasi..."
# sql_log_bin=0: user lokal replica tidak menghasilkan GTID sendiri
# (mencegah "errant transaction" yang merusak failover).
$REPLICA -e "SET SESSION sql_log_bin = 0;
             CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'%' IDENTIFIED BY '${DB_PASSWORD}';
             GRANT SELECT, REPLICATION CLIENT ON *.* TO '${DB_USERNAME}'@'%';
             SET SESSION sql_log_bin = 1;"

echo "[replica] Selesai. Replikasi aktif dan aplikasi bisa membaca dari replica."
