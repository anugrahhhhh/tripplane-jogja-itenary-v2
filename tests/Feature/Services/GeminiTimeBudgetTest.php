<?php

namespace Tests\Feature\Services;

use App\Models\Destination;
use App\Services\LlmRecommendationService;
use GuzzleHttp\Exception\TransferException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;
use Tests\TestCase;

/**
 * Batas waktu total AI. Waktu disimulasikan lewat Carbon (travel/Sleep fake),
 * jadi test tidak benar-benar menunggu.
 */
class GeminiTimeBudgetTest extends TestCase
{
    use RefreshDatabase;

    private const PRIMARY = 'generativelanguage.googleapis.com/v1beta/models/gemini-a:generateContent';

    private const FALLBACK_1 = 'generativelanguage.googleapis.com/v1beta/models/gemini-b:generateContent';

    private const FALLBACK_2 = 'generativelanguage.googleapis.com/v1beta/models/gemini-c:generateContent';

    protected function setUp(): void
    {
        parent::setUp();

        Sleep::fake(syncWithCarbon: true);
        $this->freezeTime();

        config([
            'services.gemini.key' => 'fake-test-key',
            'services.gemini.model' => 'gemini-a',
            'services.gemini.fallback_models' => 'gemini-b,gemini-c',
            'services.gemini.time_budget' => 50,
            'services.gemini.attempt_timeout' => 20,
        ]);
    }

    private function successBody(Destination $destination, string $reason): array
    {
        return [
            'candidates' => [[
                'content' => ['parts' => [[
                    'text' => json_encode(['recommendations' => [
                        ['destination_id' => $destination->id, 'reason' => $reason],
                    ]]),
                ]]],
            ]],
        ];
    }

    /** @var array<string, int> jumlah panggilan per model */
    private array $calls = [];

    /**
     * Simulasi model yang butuh $seconds detik. Seperti di dunia nyata,
     * koneksi diputus klien saat timeout tercapai (mana yang lebih dulu).
     */
    private function slowTimeout(int $seconds): \Closure
    {
        return function (Request $request, array $options) use ($seconds) {
            $this->countCall($request);
            $this->travel((int) min($seconds, $options['timeout']))->seconds();

            throw new ConnectionException('cURL error 28: Operation timed out');
        };
    }

    private function countCall(Request $request): void
    {
        preg_match('#models/([^:]+):#', $request->url(), $m);
        $this->calls[$m[1]] = ($this->calls[$m[1]] ?? 0) + 1;
    }

    public function test_user_never_waits_longer_than_the_budget(): void
    {
        $start = now()->copy();

        Http::fake([
            self::PRIMARY => $this->slowTimeout(20),
            self::FALLBACK_1 => $this->slowTimeout(20),
            self::FALLBACK_2 => $this->slowTimeout(20),
        ]);

        try {
            (new LlmRecommendationService)->recommend([]);
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Server AI', $e->getMessage());
        }

        // 20 + 20 + sisa 10 detik = tepat 50 detik, tidak lebih.
        $this->assertLessThanOrEqual(50, $start->diffInSeconds(now()));
    }

    public function test_last_fallback_only_gets_the_remaining_time(): void
    {
        $timeouts = [];

        Http::fake(function (Request $request, array $options) use (&$timeouts) {
            $timeouts[] = $options['timeout'];
            $this->travel((int) $options['timeout'])->seconds();

            throw new ConnectionException('cURL error 28: Operation timed out');
        });

        try {
            (new LlmRecommendationService)->recommend([]);
        } catch (RuntimeException) {
            // diharapkan
        }

        $this->assertSame([20.0, 20.0, 10.0], array_map('floatval', $timeouts));
    }

    public function test_remaining_models_are_skipped_when_budget_is_used_up(): void
    {
        // Model utama boleh memakai sampai 45 detik dalam satu percobaan.
        config(['services.gemini.attempt_timeout' => 45]);

        Http::fake([
            self::PRIMARY => $this->slowTimeout(45),
            self::FALLBACK_1 => $this->slowTimeout(1),
            self::FALLBACK_2 => $this->slowTimeout(1),
        ]);

        try {
            (new LlmRecommendationService)->recommend([]);
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertSame('Server AI sedang lambat merespons. Coba lagi dalam beberapa saat.', $e->getMessage());
        }

        // Sisa 5 detik < minimum 8 detik: model cadangan tidak dicoba.
        $this->assertSame(['gemini-a' => 1], $this->calls);
    }

    public function test_slow_primary_still_succeeds_through_fallback_within_budget(): void
    {
        $destination = Destination::factory()->create();

        Http::fake([
            self::PRIMARY => $this->slowTimeout(20),
            self::FALLBACK_1 => Http::response($this->successBody($destination, 'Model cadangan cepat.'), 200),
        ]);

        $result = (new LlmRecommendationService)->recommend([]);

        $this->assertSame('Model cadangan cepat.', $result[0]['reason']);
    }

    public function test_timeout_is_not_retried_on_the_same_model(): void
    {
        $destination = Destination::factory()->create();

        Http::fake([
            self::PRIMARY => $this->slowTimeout(20),
            self::FALLBACK_1 => Http::response($this->successBody($destination, 'ok'), 200),
        ]);

        (new LlmRecommendationService)->recommend([]);

        $this->assertSame(['gemini-a' => 1], $this->calls);
    }

    public function test_busy_503_is_retried_once_on_the_same_model(): void
    {
        $destination = Destination::factory()->create();

        Http::fake([
            self::PRIMARY => Http::sequence()
                ->push(['error' => 'sibuk'], 503)
                ->push($this->successBody($destination, 'Pulih setelah coba ulang.'), 200),
        ]);

        $result = (new LlmRecommendationService)->recommend([]);

        $this->assertSame('Pulih setelah coba ulang.', $result[0]['reason']);
        Http::assertSentCount(2);
        Sleep::assertSleptTimes(1);
    }

    public function test_retry_is_skipped_when_there_is_no_time_left(): void
    {
        $destination = Destination::factory()->create();

        Http::fake([
            self::PRIMARY => function () {
                $this->travel(44)->seconds();

                return Http::response(['error' => 'sibuk'], 503);
            },
            self::FALLBACK_1 => Http::response($this->successBody($destination, 'tidak boleh terpanggil'), 200),
        ]);

        try {
            (new LlmRecommendationService)->recommend([]);
            $this->fail('Seharusnya melempar RuntimeException.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('lambat merespons', $e->getMessage());
        }

        Http::assertSentCount(1);
        Sleep::assertNeverSlept();
    }

    public function test_rare_transfer_error_falls_back_instead_of_crashing(): void
    {
        $destination = Destination::factory()->create();

        Http::fake([
            self::PRIMARY => fn () => throw new TransferException('respons terpotong'),
            self::FALLBACK_1 => Http::response($this->successBody($destination, 'Aman lewat cadangan.'), 200),
        ]);

        $result = (new LlmRecommendationService)->recommend([]);

        $this->assertSame('Aman lewat cadangan.', $result[0]['reason']);
    }

    public function test_budget_config_is_clamped_to_safe_range(): void
    {
        config(['services.gemini.time_budget' => 9999, 'services.gemini.attempt_timeout' => 9999]);

        $timeouts = [];
        Http::fake(function (Request $request, array $options) use (&$timeouts) {
            $timeouts[] = $options['timeout'];
            $this->travel((int) $options['timeout'])->seconds();

            throw new ConnectionException('timeout');
        });

        try {
            (new LlmRecommendationService)->recommend([]);
        } catch (RuntimeException) {
            // diharapkan
        }

        // Total maksimum 150 detik walau config diisi sangat besar.
        $this->assertLessThanOrEqual(150, array_sum($timeouts));
        $this->assertSame(150.0, (float) $timeouts[0]);
    }

    public function test_invalid_small_config_still_gives_a_usable_minimum(): void
    {
        $destination = Destination::factory()->create();
        config(['services.gemini.time_budget' => 0, 'services.gemini.attempt_timeout' => 0]);

        $timeouts = [];
        Http::fake(function (Request $request, array $options) use (&$timeouts, $destination) {
            $timeouts[] = $options['timeout'];

            return Http::response($this->successBody($destination, 'ok'), 200);
        });

        (new LlmRecommendationService)->recommend([]);

        $this->assertSame(5.0, (float) $timeouts[0]);
    }
}
