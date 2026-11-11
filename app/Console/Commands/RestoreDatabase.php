<?php

namespace App\Console\Commands;

use App\Services\Backup\BackupManager;
use App\Support\CatalogCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Throwable;

/**
 * Pulihkan database dari backup. Langkah pengaman:
 * 1. checksum + isi backup diverifikasi dulu (backup rusak ditolak),
 * 2. backup pengaman dari kondisi database SAAT INI dibuat lebih dulu,
 * 3. aplikasi masuk mode perawatan selama restore, lalu dibuka lagi.
 */
class RestoreDatabase extends Command
{
    protected $signature = 'app:restore-database
        {file? : Path backup di disk (default: backup terbaru)}
        {--disk= : Disk sumber (default: disk backup pertama)}
        {--connection= : Koneksi database tujuan (default: koneksi utama)}
        {--list : Tampilkan daftar backup yang tersedia saja}
        {--skip-safety-backup : Jangan buat backup pengaman sebelum restore}
        {--force : Jalankan tanpa konfirmasi}';

    protected $description = 'Pulihkan database dari backup yang sudah diverifikasi';

    public function handle(BackupManager $backups): int
    {
        $disk = $this->option('disk') ?: $backups->disks()[0];
        $connection = $this->option('connection') ?: config('database.default');

        if ($this->option('list')) {
            $this->table(['Backup', 'Ukuran (KB)'], collect($backups->backups($disk))
                ->map(fn (string $file) => [$file, round($backups->disk($disk)->size($file) / 1024, 1)])
                ->all());

            return self::SUCCESS;
        }

        $file = $this->argument('file') ?: ($backups->backups($disk)[0] ?? null);

        if (! $file) {
            $this->error("Tidak ada backup di disk [{$disk}].");

            return self::FAILURE;
        }

        $this->line("Backup  : {$file} (disk {$disk})");
        $this->line("Tujuan  : koneksi [{$connection}]");

        if (! $this->option('force') && ! $this->confirm('Seluruh isi database tujuan akan DIGANTI dengan backup ini. Lanjutkan?')) {
            $this->warn('Dibatalkan.');

            return self::FAILURE;
        }

        try {
            $local = $backups->fetchVerified($disk, $file);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup terverifikasi (checksum & isi utuh).');

        try {
            if (! $this->option('skip-safety-backup')) {
                $this->line('Membuat backup pengaman dari kondisi saat ini...');

                if ($this->call('app:backup-database', ['--connection' => $connection]) !== self::SUCCESS) {
                    $this->error('Backup pengaman gagal; restore dibatalkan supaya data saat ini tidak hilang.');

                    return self::FAILURE;
                }
            }

            $wasDown = App::isDownForMaintenance();

            if (! $wasDown) {
                $this->call('down', ['--retry' => 60]);
            }

            try {
                $backups->restore($connection, $local, $backups->extensionOf($file));
                CatalogCache::flush();
            } finally {
                if (! $wasDown) {
                    $this->call('up');
                }
            }
        } catch (Throwable $e) {
            report($e);
            $this->error('Restore gagal: '.$e->getMessage());

            return self::FAILURE;
        } finally {
            @unlink($local);
        }

        $this->info('Database berhasil dipulihkan.');

        return self::SUCCESS;
    }
}
