<?php

namespace Tests\Feature\Quality;

use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\User;
use App\Notifications\ItineraryReminderNotification;
use App\Notifications\SystemHealthAlertNotification;
use App\Services\HealthAlerter;
use App\Services\LlmRecommendationService;
use App\Services\WeatherService;
use App\Support\CircuitBreaker;
use App\Support\DatabaseOutage;
use App\Support\Transaction;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\DeadlockException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Sleep;
use PDOException;
use RuntimeException;
use Tests\TestCase;

/**
 * Resilience: sistem tetap berfungsi (atau gagal dengan anggun) saat
 * layanan eksternal, database, atau email bermasalah.
 */
class ResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake();
        @unlink(storage_path('framework/health-alert-state.json'));
    }

    protected function tearDown(): void
    {
        @unlink(storage_path('framework/health-alert-state.json'));

        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // Circuit breaker
    // ------------------------------------------------------------------

    public function test_circuit_breaker_opens_after_threshold_then_half_opens_and_recovers(): void
    {
        $breaker = new CircuitBreaker('uji', threshold: 2, cooldown: 60);

        $this->assertTrue($breaker->allows());
        $breaker->recordFailure();
        $this->assertSame(CircuitBreaker::CLOSED, $breaker->state());

        $breaker->recordFailure();
        $this->assertSame(CircuitBreaker::OPEN, $breaker->state());
        $this->assertFalse($breaker->allows());
        $this->assertGreaterThan(0, $breaker->retryAfter());

        // Cooldown habis: hanya SATU percobaan diizinkan (half-open).
        $this->travel(61)->seconds();
        $this->assertSame(CircuitBreaker::HALF_OPEN, $breaker->state());
        $this->assertTrue($breaker->allows());
        $this->assertFalse($breaker->allows(), 'Percobaan kedua bersamaan harus ditahan.');

        $breaker->recordSuccess();
        $this->assertSame(CircuitBreaker::CLOSED, $breaker->state());
        $this->assertTrue($breaker->allows());
    }

    public function test_failed_probe_reopens_the_circuit_immediately(): void
    {
        $breaker = new CircuitBreaker('uji', threshold: 3, cooldown: 30);
        $breaker->recordFailure();
        $breaker->recordFailure();
        $breaker->recordFailure();

        $this->travel(31)->seconds();
        $this->assertTrue($breaker->allows());
        $breaker->recordFailure();

        $this->assertSame(CircuitBreaker::OPEN, $breaker->state());
    }

    public function test_gemini_breaker_fails_fast_after_repeated_outages(): void
    {
        config([
            'services.gemini.key' => 'fake',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.fallback_models' => '',
            'resilience.breakers.gemini' => ['threshold' => 2, 'cooldown' => 120],
        ]);
        Destination::factory()->create(['is_active' => true]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 503)]);
        $service = new LlmRecommendationService;

        foreach ([1, 2] as $attempt) {
            try {
                $service->recommend([]);
            } catch (RuntimeException) {
            }
        }

        $sentBefore = count(Http::recorded());

        try {
            $service->recommend([]);
            $this->fail('Seharusnya ditolak circuit breaker.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('gangguan', $e->getMessage());
        }

        $this->assertCount($sentBefore, Http::recorded(), 'Saat sirkuit terbuka, Gemini tidak boleh dihubungi.');
    }

    public function test_gemini_credential_errors_do_not_open_the_breaker(): void
    {
        config([
            'services.gemini.key' => 'salah',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.fallback_models' => '',
            'resilience.breakers.gemini' => ['threshold' => 1, 'cooldown' => 120],
        ]);
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 401)]);

        try {
            (new LlmRecommendationService)->recommend([]);
        } catch (RuntimeException) {
        }

        $this->assertSame(CircuitBreaker::CLOSED, CircuitBreaker::for('gemini')->state());
    }

    // ------------------------------------------------------------------
    // Cuaca: stale-if-error
    // ------------------------------------------------------------------

    private function weatherPayload(string $date): array
    {
        return ['daily' => [
            'time' => [$date],
            'weather_code' => [0],
            'temperature_2m_max' => [31.0],
            'temperature_2m_min' => [24.0],
            'precipitation_probability_max' => [10],
        ]];
    }

    public function test_weather_serves_last_known_forecast_when_the_api_is_down(): void
    {
        $date = Carbon::parse('2026-11-10');
        Http::fakeSequence('api.open-meteo.com/*')
            ->push($this->weatherPayload('2026-11-10'))
            ->pushStatus(500);

        $service = new WeatherService;
        $first = $service->forecastForDateRange(-7.8, 110.36, $date, $date);
        $this->assertEquals(31, $first['2026-11-10']['temp_max']);

        // Cache segar kedaluwarsa, API sedang gangguan -> data terakhir dipakai.
        $this->travel(46)->minutes();
        $second = $service->forecastForDateRange(-7.8, 110.36, $date, $date);

        $this->assertSame($first, $second);
    }

    public function test_weather_failure_is_only_cached_briefly_so_it_recovers_fast(): void
    {
        $date = Carbon::parse('2026-11-10');
        Http::fakeSequence('api.open-meteo.com/*')
            ->pushStatus(503)
            ->push($this->weatherPayload('2026-11-10'));

        $service = new WeatherService;
        $this->assertSame([], $service->forecastForDateRange(-7.8, 110.36, $date, $date));

        $this->travel(3)->minutes();
        $this->assertNotEmpty($service->forecastForDateRange(-7.8, 110.36, $date, $date));
    }

    public function test_weather_breaker_skips_calls_while_open(): void
    {
        config(['resilience.breakers.weather' => ['threshold' => 1, 'cooldown' => 300]]);
        Http::fake(['api.open-meteo.com/*' => Http::response([], 500)]);
        $service = new WeatherService;

        $service->forecastForDateRange(-7.8, 110.36, Carbon::parse('2026-11-10'), Carbon::parse('2026-11-10'));
        $service->forecastForDateRange(-7.9, 110.40, Carbon::parse('2026-11-11'), Carbon::parse('2026-11-11'));

        Http::assertSentCount(1);
    }

    // ------------------------------------------------------------------
    // Database
    // ------------------------------------------------------------------

    public function test_transactions_are_retried_after_a_deadlock(): void
    {
        // Koneksi terpisah tanpa transaksi pembungkus RefreshDatabase
        // (Laravel sengaja tidak mengulang deadlock di transaksi bersarang).
        config(['database.connections.tx_uji' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        $default = config('database.default');
        $attempts = 0;

        try {
            config(['database.default' => 'tx_uji']);
            DB::purge('tx_uji');

            $result = Transaction::run(function () use (&$attempts) {
                $attempts++;

                if ($attempts === 1) {
                    throw new DeadlockException('Deadlock found when trying to get lock');
                }

                return 'berhasil';
            });
        } finally {
            config(['database.default' => $default]);
        }

        $this->assertSame('berhasil', $result);
        $this->assertSame(2, $attempts);
    }

    private function connectionRefused(): QueryException
    {
        return new QueryException('mysql', 'select 1', [], new PDOException('SQLSTATE[HY000] [2002] Connection refused'));
    }

    public function test_database_outage_shows_friendly_503_with_retry_after(): void
    {
        Route::get('/_uji/db-mati', fn () => throw $this->connectionRefused());

        $this->get('/_uji/db-mati')
            ->assertStatus(503)
            ->assertHeader('Retry-After', (string) DatabaseOutage::RETRY_AFTER_SECONDS)
            ->assertSee('gangguan sementara');

        $this->getJson('/_uji/db-mati')
            ->assertStatus(503)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'gangguan'));
    }

    public function test_ordinary_query_errors_are_not_treated_as_outages(): void
    {
        $error = new QueryException('mysql', 'select * from nope', [], new PDOException('SQLSTATE[42S02]: Base table or view not found'));

        $this->assertFalse(DatabaseOutage::causedBy($error));
        $this->assertTrue(DatabaseOutage::causedBy($this->connectionRefused()));
    }

    // ------------------------------------------------------------------
    // Pengingat H-1
    // ------------------------------------------------------------------

    public function test_one_failing_reminder_does_not_stop_the_others_and_is_retried_later(): void
    {
        $good = Itinerary::factory()->create(['start_date' => today()->addDay(), 'end_date' => today()->addDays(2)]);
        $bad = Itinerary::factory()->create(['start_date' => today()->addDay(), 'end_date' => today()->addDays(2)]);

        Event::listen(NotificationSending::class, function (NotificationSending $event) use ($bad) {
            if ($event->notification instanceof ItineraryReminderNotification && $event->notifiable->is($bad->user)) {
                throw new RuntimeException('SMTP sedang mati');
            }
        });

        $this->artisan('app:send-itinerary-reminders')->assertFailed();

        $this->assertNotNull($good->fresh()->reminder_sent_at);
        $this->assertNull($bad->fresh()->reminder_sent_at, 'Yang gagal harus dicoba lagi pada jadwal berikutnya.');
    }

    public function test_reminders_are_queued_with_retries_and_rescheduled_hourly(): void
    {
        $notification = new ItineraryReminderNotification(Itinerary::factory()->create());

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertSame(5, $notification->tries);

        $this->artisan('schedule:list')->assertSuccessful();
        $event = collect(app(Schedule::class)->events())
            ->first(fn ($e) => str_contains((string) $e->command, 'app:send-itinerary-reminders'));

        $this->assertSame('0 * * * *', $event->expression);
        $this->assertTrue($event->withoutOverlapping);
    }

    // ------------------------------------------------------------------
    // Peringatan kesehatan
    // ------------------------------------------------------------------

    private function report(string $status): array
    {
        return [
            'status' => $status,
            'checked_at' => now()->toIso8601String(),
            'checks' => [
                'database' => ['status' => 'ok', 'message' => 'Terhubung'],
                'queue_worker' => ['status' => $status === 'ok' ? 'ok' : 'warning', 'message' => 'Worker mati'],
            ],
        ];
    }

    public function test_health_alerts_are_sent_on_change_throttled_and_on_recovery(): void
    {
        Notification::fake();
        config(['resilience.alert_email' => 'ops@jogjatrip.test', 'resilience.alert_cooldown_minutes' => 30]);
        $admin = User::factory()->create(['role' => 'admin']);
        $alerter = app(HealthAlerter::class);

        $this->assertNull($alerter->handle($this->report('ok')));

        $this->assertSame('problem', $alerter->handle($this->report('degraded')));
        Notification::assertSentOnDemand(SystemHealthAlertNotification::class,
            fn ($n, $channels, AnonymousNotifiable $to) => $to->routes['mail'] === 'ops@jogjatrip.test');
        Notification::assertSentTo($admin, SystemHealthAlertNotification::class);

        // Masalah yang sama dalam masa cooldown -> tidak mengirim lagi.
        $this->assertNull($alerter->handle($this->report('degraded')));

        // Memburuk (degraded -> down) -> langsung dikirim walau masih cooldown.
        $this->assertSame('problem', $alerter->handle($this->report('down')));

        $this->assertSame('recovered', $alerter->handle($this->report('ok')));
        $this->assertNull($alerter->handle($this->report('ok')));
    }

    public function test_health_alert_mail_lists_the_failing_components(): void
    {
        $mail = (new SystemHealthAlertNotification($this->report('degraded')))->toMail(new AnonymousNotifiable);

        $this->assertStringContainsString('PERINGATAN', $mail->subject);
        $this->assertTrue(collect($mail->introLines)->contains(fn ($line) => str_contains($line, 'Worker antrean: Worker mati')));
    }

    public function test_health_monitor_is_scheduled(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('app:health --notify')
            ->assertSuccessful();
    }
}
