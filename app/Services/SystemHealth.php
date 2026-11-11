<?php

namespace App\Services;

use App\Services\Backup\BackupManager;
use App\Support\CircuitBreaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Pemeriksaan kesehatan sistem untuk endpoint /health, perintah
 * `php artisan app:health`, dan kartu status di dashboard admin.
 *
 * Status tiap komponen:
 * - ok       : normal
 * - warning  : berjalan tetapi ada yang perlu diperhatikan (sistem "degraded")
 * - critical : komponen inti mati; aplikasi tidak bisa melayani pengguna
 */
class SystemHealth
{
    public const SCHEDULER_HEARTBEAT = 'health:scheduler-heartbeat';

    public const QUEUE_HEARTBEAT = 'health:queue-heartbeat';

    /**
     * @return array{status: string, checked_at: string, checks: array<string, array{status: string, message: string, critical?: bool}>}
     */
    public function report(): array
    {
        $checks = [
            'database' => $this->guard(fn () => $this->database(), critical: true),
            'database_replica' => $this->guard(fn () => $this->replica()),
            'cache' => $this->guard(fn () => $this->cache(), critical: true),
            'storage' => $this->guard(fn () => $this->storage(), critical: true),
            'queue' => $this->guard(fn () => $this->queue()),
            'queue_worker' => $this->guard(fn () => $this->queueWorker()),
            'scheduler' => $this->guard(fn () => $this->scheduler()),
            'backup' => $this->guard(fn () => $this->backup()),
            'circuit_breakers' => $this->guard(fn () => $this->breakers()),
        ];

        $statuses = array_column($checks, 'status');

        $overall = match (true) {
            in_array('critical', $statuses, true) => 'down',
            in_array('warning', $statuses, true) => 'degraded',
            default => 'ok',
        };

        return [
            'status' => $overall,
            'checked_at' => now()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    /** Dipanggil scheduler tiap menit; usianya menandakan scheduler hidup. */
    public static function beatScheduler(): void
    {
        Cache::put(self::SCHEDULER_HEARTBEAT, now()->timestamp, now()->addDay());
    }

    /** Dipanggil worker queue di setiap putaran (dibatasi 1x per 30 detik). */
    public static function beatQueueWorker(): void
    {
        static $last = 0;

        if (time() - $last < 30) {
            return;
        }

        $last = time();

        try {
            Cache::put(self::QUEUE_HEARTBEAT, $last, now()->addDay());
        } catch (Throwable) {
            // Heartbeat tidak boleh membuat worker berhenti.
        }
    }

    private function guard(callable $check, bool $critical = false): array
    {
        try {
            $result = $check();
        } catch (Throwable $e) {
            $result = [
                'status' => $critical ? 'critical' : 'warning',
                'message' => 'Gagal diperiksa: '.class_basename($e),
            ];
        }

        return $result;
    }

    private function database(): array
    {
        $start = microtime(true);
        // Ukur primary langsung (bukan lewat replika baca).
        DB::selectFromWriteConnection('select 1');
        $ms = (int) round((microtime(true) - $start) * 1000);

        return [
            'status' => $ms > 1000 ? 'warning' : 'ok',
            'message' => "Terhubung ({$ms} ms)",
        ];
    }

    private function cache(): array
    {
        $key = 'health:probe:'.bin2hex(random_bytes(4));
        Cache::put($key, 'ok', 10);
        $value = Cache::get($key);
        Cache::forget($key);

        return $value === 'ok'
            ? ['status' => 'ok', 'message' => 'Baca/tulis normal ('.config('cache.default').')']
            : ['status' => 'critical', 'message' => 'Cache tidak bisa dibaca kembali'];
    }

    private function storage(): array
    {
        $paths = [storage_path('framework/cache'), storage_path('logs')];

        foreach ($paths as $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                return ['status' => 'critical', 'message' => 'Folder tidak bisa ditulis: '.basename($path)];
            }
        }

        $free = @disk_free_space(storage_path());

        if ($free !== false && $free < 200 * 1024 * 1024) {
            return ['status' => 'warning', 'message' => 'Sisa disk kurang dari 200 MB'];
        }

        return ['status' => 'ok', 'message' => 'Bisa ditulis'];
    }

    private function queue(): array
    {
        $connection = config('queue.default');

        if ($connection === 'sync') {
            return ['status' => 'ok', 'message' => 'Mode sinkron (tanpa antrean)'];
        }

        $failed = Schema::hasTable('failed_jobs')
            ? DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count()
            : 0;

        if ($connection !== 'database') {
            return $failed > 0
                ? ['status' => 'warning', 'message' => "{$failed} job gagal dalam 24 jam"]
                : ['status' => 'ok', 'message' => "Driver {$connection}"];
        }

        $pending = DB::table('jobs')->count();
        $oldest = DB::table('jobs')->whereNull('reserved_at')->min('available_at');
        $waiting = $oldest ? max(0, now()->timestamp - (int) $oldest) : 0;

        if ($waiting > 300) {
            return ['status' => 'warning', 'message' => "Antrean macet: {$pending} job, tertua menunggu ".intdiv($waiting, 60).' menit'];
        }

        if ($failed > 0) {
            return ['status' => 'warning', 'message' => "{$pending} job menunggu, {$failed} job gagal dalam 24 jam"];
        }

        return ['status' => 'ok', 'message' => "{$pending} job menunggu"];
    }

    private function queueWorker(): array
    {
        if (config('queue.default') === 'sync') {
            return ['status' => 'ok', 'message' => 'Tidak diperlukan (mode sinkron)'];
        }

        return $this->heartbeat(self::QUEUE_HEARTBEAT, 120, 'Worker queue');
    }

    private function scheduler(): array
    {
        return $this->heartbeat(self::SCHEDULER_HEARTBEAT, 180, 'Scheduler');
    }

    private function heartbeat(string $key, int $maxAge, string $label): array
    {
        $beat = Cache::get($key);

        if (! $beat) {
            return ['status' => 'warning', 'message' => "{$label} belum pernah terdeteksi berjalan"];
        }

        $age = now()->timestamp - (int) $beat;

        return $age > $maxAge
            ? ['status' => 'warning', 'message' => "{$label} tidak aktif sejak ".intdiv($age, 60).' menit lalu']
            : ['status' => 'ok', 'message' => "{$label} aktif ({$age} dtk lalu)"];
    }

    /**
     * Replika baca (DB_READ_HOST). Tidak kritis: bila replika mati, Laravel
     * otomatis membaca dari host lain di daftar (termasuk primary).
     */
    private function replica(): array
    {
        $name = config('database.default');
        $config = config("database.connections.{$name}");

        if (empty($config['read']['host'])) {
            return ['status' => 'ok', 'message' => 'Tidak dipakai (satu server database)'];
        }

        $hosts = (array) $config['read']['host'];
        $down = [];
        $lagging = [];

        foreach ($hosts as $host) {
            $probe = "health_replica_{$host}";
            config(["database.connections.{$probe}" => array_merge(
                array_diff_key($config, array_flip(['read', 'write', 'sticky'])),
                ['host' => $host],
            )]);

            try {
                $status = (array) (DB::connection($probe)->selectOne('SHOW REPLICA STATUS') ?? []);

                if ($status !== []) {
                    $running = ($status['Replica_IO_Running'] ?? '') === 'Yes' && ($status['Replica_SQL_Running'] ?? '') === 'Yes';
                    $lag = $status['Seconds_Behind_Source'] ?? null;

                    if (! $running) {
                        $lagging[] = "{$host} (replikasi berhenti)";
                    } elseif ($lag !== null && (int) $lag > 60) {
                        $lagging[] = "{$host} (tertinggal {$lag} dtk)";
                    }
                }
            } catch (Throwable $e) {
                // Tanpa hak REPLICATION CLIENT, cukup pastikan host menjawab.
                try {
                    DB::connection($probe)->select('select 1');
                } catch (Throwable) {
                    $down[] = $host;
                }
            } finally {
                DB::purge($probe);
            }
        }

        if ($down !== [] || $lagging !== []) {
            return ['status' => 'warning', 'message' => trim(
                ($down ? 'Tidak terhubung: '.implode(', ', $down).'. ' : '').
                ($lagging ? 'Bermasalah: '.implode(', ', $lagging).'.' : '')
            )];
        }

        return ['status' => 'ok', 'message' => count($hosts).' host baca sehat'];
    }

    /**
     * Backup di SEMUA disk tujuan: harus ada, cukup baru, dan punya checksum.
     */
    private function backup(): array
    {
        $manager = app(BackupManager::class);
        $maxAge = (int) config('backup.max_age_hours', 26);
        $problems = [];
        $newest = null;

        foreach ($manager->disks() as $diskName) {
            try {
                $files = $manager->backups($diskName);

                if ($files === []) {
                    $problems[] = "belum ada backup di [{$diskName}]";

                    continue;
                }

                $disk = $manager->disk($diskName);
                $age = intdiv(now()->timestamp - $disk->lastModified($files[0]), 3600);
                $newest = $newest === null ? $age : min($newest, $age);

                if ($age >= $maxAge) {
                    $problems[] = "backup terakhir di [{$diskName}] {$age} jam lalu";
                } elseif (! $disk->exists($files[0].'.sha256')) {
                    $problems[] = "backup terbaru di [{$diskName}] tanpa checksum";
                }
            } catch (Throwable $e) {
                $problems[] = "disk [{$diskName}] tidak bisa dibaca";
            }
        }

        if ($problems !== []) {
            return ['status' => 'warning', 'message' => ucfirst(implode('; ', $problems))];
        }

        $locations = count($manager->disks());
        $note = $locations > 1 ? "{$locations} lokasi" : '1 lokasi (tambahkan disk offsite untuk redundansi)';

        return ['status' => 'ok', 'message' => "Terverifikasi di {$note}, terbaru {$newest} jam lalu"];
    }

    private function breakers(): array
    {
        $open = collect(array_keys(config('resilience.breakers', [])))
            ->mapWithKeys(fn (string $service) => [$service => CircuitBreaker::for($service)])
            ->filter(fn (CircuitBreaker $breaker) => $breaker->state() === CircuitBreaker::OPEN)
            ->map(fn (CircuitBreaker $breaker, string $service) => "{$service} (pulih dicoba {$breaker->retryAfter()} dtk lagi)");

        return $open->isEmpty()
            ? ['status' => 'ok', 'message' => 'Semua layanan eksternal normal']
            : ['status' => 'warning', 'message' => 'Sirkuit terbuka: '.$open->implode(', ')];
    }
}
