<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Pengaman: test TIDAK BOLEH berjalan di database sungguhan.
     *
     * RefreshDatabase mengosongkan semua tabel (migrate:fresh). Kalau test
     * tidak sengaja memakai database pengembangan/produksi, misalnya karena
     * `php artisan test --env=...` (opsi itu membuat DB_CONNECTION dari file
     * .env ikut terpakai) atau config:cache masih aktif, seluruh data hilang.
     * Pemeriksaan ini dijalankan SEBELUM RefreshDatabase.
     */
    protected function setUpTraits()
    {
        $this->ensureTestDatabase();

        return parent::setUpTraits();
    }

    protected function ensureTestDatabase(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");
        $database = (string) config("database.connections.{$connection}.database");

        $isSqliteMemory = $driver === 'sqlite' && $database === ':memory:';
        $isDedicatedTestDatabase = (bool) preg_match('/(^|_)test(ing)?$/i', basename($database, '.sqlite'));

        if (! $isSqliteMemory && ! $isDedicatedTestDatabase) {
            throw new RuntimeException(sprintf(
                'Test dihentikan: koneksi "%s" (%s) mengarah ke database "%s", bukan database test. '
                .'Jalankan `php artisan config:clear` lalu `php artisan test` TANPA opsi --env. '
                .'Database test harus SQLite :memory: atau bernama *_test.',
                $connection,
                $driver,
                $database,
            ));
        }
    }
}
