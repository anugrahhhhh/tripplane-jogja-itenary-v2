<?php

namespace Tests\Feature\Quality;

use App\Services\SystemHealth;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Availability: health check, backup database, dan index performa.
 */
class AvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_health_endpoint_reports_status_without_leaking_details(): void
    {
        $this->getJson('/health')
            ->assertOk()
            ->assertJsonStructure(['status', 'checked_at'])
            ->assertJsonMissingPath('checks')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_health_details_require_the_token(): void
    {
        config(['app.health_token' => 'rahasia-monitor']);

        $this->getJson('/health', ['X-Health-Token' => 'salah'])->assertJsonMissingPath('checks');

        $this->getJson('/health', ['X-Health-Token' => 'rahasia-monitor'])
            ->assertOk()
            ->assertJsonPath('checks.database.status', 'ok')
            ->assertJsonPath('checks.cache.status', 'ok')
            ->assertJsonPath('checks.storage.status', 'ok');
    }

    public function test_health_endpoint_returns_503_when_the_database_is_down(): void
    {
        config(['database.connections.mati' => [
            'driver' => 'sqlite',
            'database' => storage_path('framework/tidak-ada/db.sqlite'),
            'prefix' => '',
        ]]);
        $default = config('database.default');

        try {
            config(['database.default' => 'mati']);
            $response = $this->getJson('/health');
        } finally {
            config(['database.default' => $default]);
        }

        $response->assertStatus(503)->assertJsonPath('status', 'down');
    }

    public function test_heartbeats_mark_scheduler_and_worker_alive(): void
    {
        config(['queue.default' => 'database']);
        $before = app(SystemHealth::class)->report()['checks'];
        $this->assertSame('warning', $before['scheduler']['status']);
        $this->assertSame('warning', $before['queue_worker']['status']);

        SystemHealth::beatScheduler();
        Cache::put(SystemHealth::QUEUE_HEARTBEAT, now()->timestamp);

        $after = app(SystemHealth::class)->report()['checks'];
        $this->assertSame('ok', $after['scheduler']['status']);
        $this->assertSame('ok', $after['queue_worker']['status']);
    }

    public function test_health_command_exits_successfully_when_core_components_work(): void
    {
        $this->artisan('app:health')->assertSuccessful();
    }

    public function test_backup_command_writes_a_compressed_copy_and_prunes_old_backups(): void
    {
        Storage::fake('local');
        config(['backup.disks' => ['local'], 'backup.path' => 'backups', 'backup.keep_days' => 14]);

        $file = tempnam(sys_get_temp_dir(), 'jtdb');
        config(['database.connections.cadangan' => ['driver' => 'sqlite', 'database' => $file, 'prefix' => '']]);
        DB::connection('cadangan')->statement('create table catatan (isi text)');
        DB::connection('cadangan')->insert("insert into catatan values ('halo')");
        DB::disconnect('cadangan');

        $disk = Storage::disk('local');
        $disk->put('backups/jogjatrip-20200101-000000.sqlite.gz', 'lama');
        touch($disk->path('backups/jogjatrip-20200101-000000.sqlite.gz'), now()->subDays(30)->timestamp);

        try {
            $this->artisan('app:backup-database', ['--connection' => 'cadangan'])->assertSuccessful();
        } finally {
            @unlink($file);
        }

        $files = array_values(array_filter($disk->files('backups'), fn ($f) => str_ends_with($f, '.gz')));
        $this->assertCount(1, $files, 'Backup lama harus terhapus, backup baru tersimpan.');
        $this->assertTrue($disk->exists($files[0].'.sha256'));
        $this->assertStringEndsWith('.sqlite.gz', $files[0]);
        $this->assertStringStartsWith('SQLite format 3', gzdecode($disk->get($files[0])));

        $health = app(SystemHealth::class)->report()['checks']['backup'];
        $this->assertSame('ok', $health['status']);
    }

    public function test_backup_of_in_memory_database_fails_cleanly(): void
    {
        Storage::fake('local');

        $this->artisan('app:backup-database')->assertFailed();
        $this->assertSame([], Storage::disk('local')->files('backups'));
    }

    public function test_backup_and_heartbeat_are_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('app:backup-database')
            ->expectsOutputToContain('health-heartbeat')
            ->assertSuccessful();
    }

    public function test_performance_indexes_exist(): void
    {
        $this->assertTrue(Schema::hasIndex('destinations', 'destinations_active_category_index'));
        $this->assertTrue(Schema::hasIndex('reviews', 'reviews_destination_visible_index'));
        $this->assertTrue(Schema::hasIndex('itinerary_items', 'itinerary_items_order_index'));
        $this->assertTrue(Schema::hasIndex('itineraries', 'itineraries_reminder_index'));
    }

    public function test_admin_health_card_renders_every_check(): void
    {
        $html = view('admin.partials.system-health', ['health' => app(SystemHealth::class)->report()])->render();

        $this->assertStringContainsString('Kesehatan Sistem', $html);
        $this->assertStringContainsString('Database', $html);
        $this->assertStringContainsString('Backup database', $html);
    }
}
