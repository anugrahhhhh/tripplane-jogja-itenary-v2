<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupManager;
use Illuminate\Console\Command;
use Throwable;

/**
 * Backup database: dump -> verifikasi -> simpan ke semua disk
 * config('backup.disks') + checksum -> hapus backup lama.
 *
 * Dijadwalkan harian di routes/console.php. Restore: app:restore-database.
 */
class BackupDatabase extends Command
{
    protected $signature = 'app:backup-database
        {--connection= : Koneksi database (default: koneksi utama)}
        {--keep= : Simpan backup selama N hari (default: config backup.keep_days)}';

    protected $description = 'Backup database ke beberapa lokasi, verifikasi isinya, dan hapus backup lama';

    public function handle(BackupManager $backups): int
    {
        $connection = $this->option('connection') ?: config('database.default');

        try {
            $result = $backups->create($connection);
        } catch (Throwable $e) {
            report($e);
            $this->error('Backup gagal: '.$e->getMessage());

            return self::FAILURE;
        }

        $size = round($result['size'] / 1024, 1);
        $this->info("Backup terverifikasi: {$result['name']} ({$size} KB)");
        $this->line('Tersimpan di: '.implode(', ', $result['stored']));
        $this->line('SHA-256: '.$result['checksum']);

        foreach ($result['failed'] as $disk => $error) {
            $this->warn("Gagal disimpan ke disk [{$disk}]: {$error}");
        }

        $deleted = $backups->prune((int) ($this->option('keep') ?: config('backup.keep_days')));

        if ($deleted > 0) {
            $this->line("{$deleted} backup lama dihapus.");
        }

        // Sebagian disk gagal = backup ada, tetapi redundansi berkurang.
        return $result['failed'] === [] ? self::SUCCESS : self::FAILURE;
    }
}
