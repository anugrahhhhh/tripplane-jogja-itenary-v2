<?php

namespace Tests\Feature\Quality;

use App\Http\Middleware\ReadYourWrites;
use App\Models\User;
use App\Services\Backup\BackupManager;
use App\Services\SystemHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

/**
 * Availability: backup berlapis yang terverifikasi, restore yang aman,
 * replika baca, dan konsistensi read-your-writes.
 */
class BackupRestoreTest extends TestCase
{
    use RefreshDatabase;

    private string $dbFile;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::fake('offsite');
        config(['backup.disks' => ['local', 'offsite'], 'backup.path' => 'backups']);

        $this->dbFile = tempnam(sys_get_temp_dir(), 'jtdb');
        config(['database.connections.cadangan' => ['driver' => 'sqlite', 'database' => $this->dbFile, 'prefix' => '']]);
        DB::connection('cadangan')->statement('create table catatan (isi text)');
        DB::connection('cadangan')->insert("insert into catatan values ('versi-asli')");
    }

    protected function tearDown(): void
    {
        DB::purge('cadangan');
        @unlink($this->dbFile);
        @unlink($this->dbFile.'.restore-tmp');

        parent::tearDown();
    }

    private function isi(): array
    {
        return DB::connection('cadangan')->table('catatan')->pluck('isi')->all();
    }

    public function test_backup_is_written_to_every_disk_with_a_checksum(): void
    {
        $this->artisan('app:backup-database', ['--connection' => 'cadangan'])->assertSuccessful();

        foreach (['local', 'offsite'] as $disk) {
            $files = app(BackupManager::class)->backups($disk);
            $this->assertCount(1, $files, "Backup harus ada di disk {$disk}.");

            $checksum = strtok(Storage::disk($disk)->get($files[0].'.sha256'), ' ');
            $this->assertSame(hash('sha256', Storage::disk($disk)->get($files[0])), $checksum);
        }

        $health = app(SystemHealth::class)->report()['checks']['backup'];
        $this->assertSame('ok', $health['status']);
        $this->assertStringContainsString('2 lokasi', $health['message']);
    }

    public function test_one_unavailable_disk_does_not_prevent_backup_on_the_others(): void
    {
        $blocker = tempnam(sys_get_temp_dir(), 'jtblock'); // file, bukan folder -> tidak bisa ditulisi
        config([
            'filesystems.disks.rusak' => ['driver' => 'local', 'root' => $blocker.'/x', 'throw' => true],
            'backup.disks' => ['rusak', 'local'],
        ]);

        try {
            $this->artisan('app:backup-database', ['--connection' => 'cadangan'])
                ->expectsOutputToContain('Gagal disimpan ke disk [rusak]')
                ->assertFailed();
        } finally {
            @unlink($blocker);
        }

        $this->assertCount(1, app(BackupManager::class)->backups('local'));
    }

    public function test_truncated_sql_dump_is_rejected(): void
    {
        $gz = tempnam(sys_get_temp_dir(), 'jtgz');
        file_put_contents($gz, gzencode("CREATE TABLE a (id int);\nINSERT INTO a VALUES (1"));

        try {
            $this->expectException(RuntimeException::class);
            $this->expectExceptionMessage('tidak lengkap');
            app(BackupManager::class)->verify($gz, 'sql');
        } finally {
            @unlink($gz);
        }
    }

    public function test_complete_sql_dump_passes_verification(): void
    {
        $gz = tempnam(sys_get_temp_dir(), 'jtgz');
        file_put_contents($gz, gzencode("CREATE TABLE a (id int);\n-- Dump completed on 2026-11-05 02:00:01\n"));

        try {
            app(BackupManager::class)->verify($gz, 'sql');
            $this->addToAssertionCount(1);
        } finally {
            @unlink($gz);
        }
    }

    public function test_restore_brings_back_the_backed_up_data_and_keeps_a_safety_copy(): void
    {
        $this->artisan('app:backup-database', ['--connection' => 'cadangan'])->assertSuccessful();
        $original = app(BackupManager::class)->backups('local')[0];

        DB::connection('cadangan')->table('catatan')->update(['isi' => 'rusak-oleh-bug']);
        $this->travel(2)->seconds();

        $this->artisan('app:restore-database', ['file' => $original, '--connection' => 'cadangan', '--force' => true])
            ->expectsOutputToContain('Backup terverifikasi')
            ->expectsOutputToContain('Database berhasil dipulihkan')
            ->assertSuccessful();

        $this->assertSame(['versi-asli'], $this->isi());
        $this->assertCount(2, app(BackupManager::class)->backups('local'), 'Backup pengaman kondisi sebelum restore harus dibuat.');
        $this->assertFalse(app()->isDownForMaintenance(), 'Aplikasi harus dibuka lagi setelah restore.');
    }

    public function test_restore_refuses_a_backup_whose_checksum_does_not_match(): void
    {
        $this->artisan('app:backup-database', ['--connection' => 'cadangan'])->assertSuccessful();
        $file = app(BackupManager::class)->backups('local')[0];
        Storage::disk('local')->put($file.'.sha256', str_repeat('0', 64).'  x');

        DB::connection('cadangan')->table('catatan')->update(['isi' => 'data-terbaru']);

        $this->artisan('app:restore-database', ['file' => $file, '--connection' => 'cadangan', '--force' => true])
            ->expectsOutputToContain('Checksum backup tidak cocok')
            ->assertFailed();

        $this->assertSame(['data-terbaru'], $this->isi(), 'Data tidak boleh berubah bila backup rusak.');
    }

    public function test_restore_asks_for_confirmation(): void
    {
        $this->artisan('app:backup-database', ['--connection' => 'cadangan'])->assertSuccessful();

        $this->artisan('app:restore-database', ['--connection' => 'cadangan'])
            ->expectsConfirmation('Seluruh isi database tujuan akan DIGANTI dengan backup ini. Lanjutkan?', 'no')
            ->assertFailed();
    }

    public function test_pruning_always_keeps_the_newest_backup(): void
    {
        $this->artisan('app:backup-database', ['--connection' => 'cadangan'])->assertSuccessful();
        $only = app(BackupManager::class)->backups('local')[0];
        touch(Storage::disk('local')->path($only), now()->subDays(60)->timestamp);

        $this->assertSame(0, app(BackupManager::class)->prune(14));
        $this->assertTrue(Storage::disk('local')->exists($only));
    }

    public function test_unreachable_read_replica_is_a_warning_not_an_outage(): void
    {
        config(['database.connections.mysql_uji' => [
            'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 1, 'database' => 'x',
            'username' => 'x', 'password' => '', 'options' => [\PDO::ATTR_TIMEOUT => 1],
            'read' => ['host' => ['127.0.0.1']], 'write' => ['host' => ['127.0.0.1']],
        ]]);
        $default = config('database.default');
        $replica = new ReflectionMethod(SystemHealth::class, 'replica');

        try {
            config(['database.default' => 'mysql_uji']);
            $result = $replica->invoke(app(SystemHealth::class));
        } finally {
            config(['database.default' => $default]);
        }

        $this->assertSame('warning', $result['status']);
        $this->assertStringContainsString('Tidak terhubung', $result['message']);
    }

    public function test_replica_check_is_skipped_without_read_hosts(): void
    {
        $result = (new ReflectionMethod(SystemHealth::class, 'replica'))->invoke(app(SystemHealth::class));

        $this->assertSame('ok', $result['status']);
    }

    public function test_writes_pin_the_next_requests_to_the_primary_when_a_replica_is_used(): void
    {
        $user = User::factory()->create();
        $connection = config('database.default');
        config(["database.connections.{$connection}.read" => ['database' => ':memory:']]);

        $this->actingAs($user)->post(route('itineraries.store'), [
            'title' => 'Liburan', 'start_date' => now()->addDay()->toDateString(), 'end_date' => now()->addDay()->toDateString(),
        ]);

        $this->assertGreaterThanOrEqual(now()->timestamp, session(ReadYourWrites::SESSION_KEY));
    }

    public function test_read_your_writes_is_inactive_without_a_replica(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('itineraries.store'), [
            'title' => 'Liburan', 'start_date' => now()->addDay()->toDateString(), 'end_date' => now()->addDay()->toDateString(),
        ]);

        $this->assertNull(session(ReadYourWrites::SESSION_KEY));
    }
}
