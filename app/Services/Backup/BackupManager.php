<?php

namespace App\Services\Backup;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Membuat, memverifikasi, menyebarkan, dan memulihkan backup database.
 *
 * Setiap backup:
 * 1. di-dump lalu dikompres gzip ke file sementara,
 * 2. DIVERIFIKASI sebelum disimpan (gzip utuh, dump lengkap / integritas SQLite),
 * 3. ditulis ke semua disk di config('backup.disks') beserta file .sha256,
 * 4. ukuran di tiap disk dicocokkan dengan file asli.
 */
class BackupManager
{
    /** @return array<int, string> */
    public function disks(): array
    {
        return config('backup.disks') ?: ['local'];
    }

    public function disk(string $name): Filesystem
    {
        return Storage::disk($name);
    }

    public function path(): string
    {
        return trim((string) config('backup.path', 'backups'), '/');
    }

    public function prefix(): string
    {
        return str((string) config('app.name', 'app'))->slug()->value() ?: 'app';
    }

    /**
     * Daftar backup di satu disk, terbaru dulu (nama file memuat waktu).
     *
     * @return array<int, string>
     */
    public function backups(string $disk): array
    {
        return collect($this->disk($disk)->files($this->path()))
            ->filter(fn (string $file) => str_starts_with(basename($file), $this->prefix().'-') && str_ends_with($file, '.gz'))
            ->sortDesc()
            ->values()
            ->all();
    }

    /**
     * Buat backup dan simpan ke semua disk.
     *
     * @return array{name: string, size: int, checksum: string, stored: array<int, string>, failed: array<string, string>}
     */
    public function create(string $connection): array
    {
        $config = config("database.connections.{$connection}");

        if (! $config) {
            throw new RuntimeException("Koneksi database [{$connection}] tidak ditemukan.");
        }

        $temp = tempnam(sys_get_temp_dir(), 'jtbackup');

        try {
            $extension = $this->dump($config, $temp);
            $this->verify($temp, $extension);

            $checksum = hash_file('sha256', $temp);
            $size = filesize($temp);
            $name = sprintf('%s/%s-%s.%s.gz', $this->path(), $this->prefix(), now()->format('Ymd-His'), $extension);

            $stored = [];
            $failed = [];

            foreach ($this->disks() as $diskName) {
                try {
                    $this->store($diskName, $name, $temp, $checksum, $size);
                    $stored[] = $diskName;
                } catch (Throwable $e) {
                    report($e);
                    $failed[$diskName] = $e->getMessage();
                }
            }

            if ($stored === []) {
                throw new RuntimeException('Backup tidak tersimpan di disk mana pun: '.implode('; ', $failed));
            }

            return compact('name', 'size', 'checksum', 'stored', 'failed');
        } finally {
            @unlink($temp);
        }
    }

    /**
     * Hapus backup lebih tua dari $keepDays di semua disk. Backup terbaru
     * di tiap disk SELALU disisakan, walaupun sudah tua (mis. backup macet
     * berhari-hari) supaya tidak pernah tersisa nol backup.
     */
    public function prune(int $keepDays): int
    {
        if ($keepDays < 1) {
            return 0;
        }

        $cutoff = now()->subDays($keepDays)->timestamp;
        $deleted = 0;

        foreach ($this->disks() as $diskName) {
            try {
                $disk = $this->disk($diskName);

                foreach (array_slice($this->backups($diskName), 1) as $file) {
                    if ($disk->lastModified($file) < $cutoff) {
                        $disk->delete([$file, $file.'.sha256']);
                        $deleted++;
                    }
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $deleted;
    }

    /**
     * Unduh backup ke file sementara dan pastikan utuh (checksum + isi).
     * Pemanggil wajib menghapus file yang dikembalikan.
     */
    public function fetchVerified(string $diskName, string $file): string
    {
        $disk = $this->disk($diskName);

        if (! $disk->exists($file)) {
            throw new RuntimeException("File backup [{$file}] tidak ditemukan di disk [{$diskName}].");
        }

        $temp = tempnam(sys_get_temp_dir(), 'jtrestore');
        $in = $disk->readStream($file);
        $out = fopen($temp, 'wb');
        stream_copy_to_stream($in, $out);
        fclose($out);
        is_resource($in) && fclose($in);

        try {
            if ($disk->exists($file.'.sha256')) {
                $expected = trim(strtok((string) $disk->get($file.'.sha256'), ' '));

                if (! hash_equals($expected, hash_file('sha256', $temp))) {
                    throw new RuntimeException('Checksum backup tidak cocok: file rusak atau berubah.');
                }
            }

            $this->verify($temp, $this->extensionOf($file));
        } catch (Throwable $e) {
            @unlink($temp);

            throw $e;
        }

        return $temp;
    }

    /** Pulihkan database dari file .gz lokal yang sudah diverifikasi. */
    public function restore(string $connection, string $gzPath, string $extension): void
    {
        $config = config("database.connections.{$connection}");

        if (! $config) {
            throw new RuntimeException("Koneksi database [{$connection}] tidak ditemukan.");
        }

        match ($config['driver']) {
            'sqlite' => $this->restoreSqlite($connection, $config, $gzPath, $extension),
            'mysql', 'mariadb' => $this->restoreWithProcess($this->mysqlRestoreCommand($config), ['MYSQL_PWD' => (string) ($config['password'] ?? '')], $gzPath, $extension),
            'pgsql' => $this->restoreWithProcess($this->pgsqlRestoreCommand($config), ['PGPASSWORD' => (string) ($config['password'] ?? '')], $gzPath, $extension),
            default => throw new RuntimeException("Driver {$config['driver']} belum didukung."),
        };

        DB::purge($connection);
    }

    public function extensionOf(string $file): string
    {
        return str_ends_with($file, '.sqlite.gz') ? 'sqlite' : 'sql';
    }

    // ------------------------------------------------------------------
    // Verifikasi
    // ------------------------------------------------------------------

    /**
     * Pastikan file gzip bisa dibaca utuh dan isinya lengkap.
     * Lempar RuntimeException bila backup tidak layak dipakai.
     */
    public function verify(string $gzPath, string $extension): void
    {
        $gz = @gzopen($gzPath, 'rb');

        if (! $gz) {
            throw new RuntimeException('Verifikasi gagal: file gzip tidak bisa dibuka.');
        }

        $head = '';
        $tail = '';
        $total = 0;

        while (! gzeof($gz)) {
            $chunk = gzread($gz, 1024 * 512);

            if ($chunk === false) {
                gzclose($gz);

                throw new RuntimeException('Verifikasi gagal: data gzip rusak.');
            }

            if ($head === '') {
                $head = substr($chunk, 0, 64);
            }

            $tail = substr($tail.$chunk, -4096);
            $total += strlen($chunk);
        }

        gzclose($gz);

        if ($total === 0) {
            throw new RuntimeException('Verifikasi gagal: backup kosong.');
        }

        if ($extension === 'sqlite') {
            if (! str_starts_with($head, 'SQLite format 3')) {
                throw new RuntimeException('Verifikasi gagal: bukan file database SQLite.');
            }

            $this->checkSqliteIntegrity($gzPath);

            return;
        }

        // mysqldump/mariadb-dump & pg_dump menulis penanda ini hanya bila
        // dump selesai sampai akhir (tidak terpotong di tengah).
        if (! preg_match('/-- Dump completed|PostgreSQL database dump complete/', $tail)) {
            throw new RuntimeException('Verifikasi gagal: dump SQL tidak lengkap (terpotong).');
        }
    }

    private function checkSqliteIntegrity(string $gzPath): void
    {
        $plain = tempnam(sys_get_temp_dir(), 'jtverify');

        try {
            $this->gunzipTo($gzPath, $plain);
            $pdo = new PDO('sqlite:'.$plain);
            $result = $pdo->query('PRAGMA integrity_check')->fetchColumn();
            $pdo = null;

            if ($result !== 'ok') {
                throw new RuntimeException('Verifikasi gagal: integrity_check SQLite = '.$result);
            }
        } finally {
            @unlink($plain);
        }
    }

    // ------------------------------------------------------------------
    // Dump
    // ------------------------------------------------------------------

    private function dump(array $config, string $target): string
    {
        return match ($config['driver']) {
            'sqlite' => $this->dumpSqlite($config, $target),
            'mysql', 'mariadb' => $this->dumpWithProcess($this->mysqlDumpCommand($config), ['MYSQL_PWD' => (string) ($config['password'] ?? '')], $target),
            'pgsql' => $this->dumpWithProcess($this->pgsqlDumpCommand($config), ['PGPASSWORD' => (string) ($config['password'] ?? '')], $target),
            default => throw new RuntimeException("Driver {$config['driver']} belum didukung."),
        };
    }

    private function dumpSqlite(array $config, string $target): string
    {
        $path = $config['database'] ?? '';

        if ($path === ':memory:' || ! is_file($path)) {
            throw new RuntimeException('Database SQLite in-memory/tidak ditemukan tidak bisa di-backup.');
        }

        // VACUUM INTO menghasilkan salinan konsisten walau database sedang dipakai.
        $snapshot = tempnam(sys_get_temp_dir(), 'jtsnap');
        @unlink($snapshot);

        try {
            $pdo = new PDO('sqlite:'.$path);
            $pdo->exec('VACUUM INTO '.$pdo->quote($snapshot));
            $pdo = null;

            $this->gzipFile($snapshot, $target);
        } finally {
            @unlink($snapshot);
        }

        return 'sqlite';
    }

    private function mysqlDumpCommand(array $config): array
    {
        return [
            config('backup.mysqldump'),
            '--host='.$this->host($config, '127.0.0.1'),
            '--port='.($config['port'] ?? 3306),
            '--user='.($config['username'] ?? 'root'),
            '--single-transaction',
            '--quick',
            '--no-tablespaces',
            '--default-character-set=utf8mb4',
            $config['database'],
        ];
    }

    private function pgsqlDumpCommand(array $config): array
    {
        return [
            config('backup.pg_dump'),
            '--host='.$this->host($config, '127.0.0.1'),
            '--port='.($config['port'] ?? 5432),
            '--username='.($config['username'] ?? 'postgres'),
            '--no-owner',
            $config['database'],
        ];
    }

    private function dumpWithProcess(array $command, array $env, string $target): string
    {
        $gz = gzopen($target, 'wb6');
        $stderr = '';

        $process = new Process($command, null, $env, null, 1800);
        $process->run(function (string $type, string $buffer) use ($gz, &$stderr) {
            $type === Process::OUT ? gzwrite($gz, $buffer) : $stderr .= $buffer;
        });

        gzclose($gz);

        if (! $process->isSuccessful()) {
            throw new RuntimeException(trim($stderr) ?: 'Perintah dump keluar dengan kode '.$process->getExitCode());
        }

        return 'sql';
    }

    // ------------------------------------------------------------------
    // Restore
    // ------------------------------------------------------------------

    private function restoreSqlite(string $connection, array $config, string $gzPath, string $extension): void
    {
        if ($extension !== 'sqlite') {
            throw new RuntimeException('Backup ini bukan backup SQLite.');
        }

        $path = $config['database'] ?? '';

        if ($path === '' || $path === ':memory:') {
            throw new RuntimeException('Tidak bisa me-restore ke database SQLite in-memory.');
        }

        DB::disconnect($connection);

        // Tulis ke file sementara di folder yang sama lalu rename (atomik):
        // database tidak pernah berada dalam keadaan setengah tertulis.
        $staging = $path.'.restore-tmp';
        $this->gunzipTo($gzPath, $staging);

        if (! @rename($staging, $path)) {
            @copy($staging, $path);
            @unlink($staging);
        }
    }

    private function mysqlRestoreCommand(array $config): array
    {
        return [
            config('backup.mysql'),
            '--host='.$this->host($config, '127.0.0.1'),
            '--port='.($config['port'] ?? 3306),
            '--user='.($config['username'] ?? 'root'),
            '--default-character-set=utf8mb4',
            $config['database'],
        ];
    }

    private function pgsqlRestoreCommand(array $config): array
    {
        return [
            config('backup.psql'),
            '--host='.$this->host($config, '127.0.0.1'),
            '--port='.($config['port'] ?? 5432),
            '--username='.($config['username'] ?? 'postgres'),
            '--set=ON_ERROR_STOP=1',
            $config['database'],
        ];
    }

    private function restoreWithProcess(array $command, array $env, string $gzPath, string $extension): void
    {
        if ($extension !== 'sql') {
            throw new RuntimeException('Backup ini bukan dump SQL.');
        }

        $input = fopen('compress.zlib://'.$gzPath, 'rb');
        $process = new Process($command, null, $env, $input, 3600);
        $process->run();
        is_resource($input) && fclose($input);

        if (! $process->isSuccessful()) {
            throw new RuntimeException('Restore gagal: '.(trim($process->getErrorOutput()) ?: 'kode keluar '.$process->getExitCode()));
        }
    }

    // ------------------------------------------------------------------
    // Utilitas
    // ------------------------------------------------------------------

    private function store(string $diskName, string $name, string $temp, string $checksum, int $size): void
    {
        $disk = $this->disk($diskName);
        $stream = fopen($temp, 'rb');

        try {
            if (! $disk->writeStream($name, $stream)) {
                throw new RuntimeException("Gagal menulis ke disk [{$diskName}].");
            }
        } finally {
            is_resource($stream) && fclose($stream);
        }

        $disk->put($name.'.sha256', $checksum.'  '.basename($name)."\n");

        if ((int) $disk->size($name) !== $size) {
            $disk->delete([$name, $name.'.sha256']);

            throw new RuntimeException("Ukuran backup di disk [{$diskName}] tidak cocok; salinan dibuang.");
        }
    }

    /** Host utama (write) walau koneksi memakai konfigurasi read/write. */
    private function host(array $config, string $default): string
    {
        $host = $config['write']['host'] ?? $config['host'] ?? $default;

        return is_array($host) ? (string) reset($host) : (string) $host;
    }

    private function gzipFile(string $source, string $target): void
    {
        $in = fopen($source, 'rb');
        $out = gzopen($target, 'wb6');

        while (! feof($in)) {
            gzwrite($out, fread($in, 1024 * 512));
        }

        fclose($in);
        gzclose($out);
    }

    private function gunzipTo(string $gzPath, string $target): void
    {
        $in = gzopen($gzPath, 'rb');
        $out = fopen($target, 'wb');

        while (! gzeof($in)) {
            fwrite($out, gzread($in, 1024 * 512));
        }

        gzclose($in);
        fclose($out);
    }
}
