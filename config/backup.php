<?php

return [

    /*
    | Disk tujuan backup database (lihat config/filesystems.php), dipisah
    | koma. Backup ditulis ke SEMUA disk; satu disk gagal tidak membatalkan
    | disk lain. Contoh produksi: "local,offsite" atau "local,s3".
    |
    | - local   : storage/app/private (pemulihan cepat)
    | - offsite : folder BACKUP_OFFSITE_ROOT, untuk mount penyimpanan di luar
    |             server (NFS, disk terpisah, network drive)
    | - s3      : butuh `composer require league/flysystem-aws-s3-v3`
    */
    'disks' => array_values(array_filter(array_map('trim', explode(',', (string) env('BACKUP_DISKS', env('BACKUP_DISK', 'local')))))),

    'path' => env('BACKUP_PATH', 'backups'),

    // Backup lebih tua dari ini dihapus otomatis (minimal 1 backup selalu disisakan).
    'keep_days' => (int) env('BACKUP_KEEP_DAYS', 14),

    // Backup dianggap terlambat bila yang terbaru lebih tua dari ini (jam).
    'max_age_hours' => (int) env('BACKUP_MAX_AGE_HOURS', 26),

    // Lokasi biner dump/restore (default: dari PATH).
    'mysqldump' => env('BACKUP_MYSQLDUMP_PATH', 'mysqldump'),
    'mysql' => env('BACKUP_MYSQL_PATH', 'mysql'),
    'pg_dump' => env('BACKUP_PG_DUMP_PATH', 'pg_dump'),
    'psql' => env('BACKUP_PSQL_PATH', 'psql'),

];
