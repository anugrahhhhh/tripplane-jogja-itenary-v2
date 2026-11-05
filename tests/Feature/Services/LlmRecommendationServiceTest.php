<?php

namespace Tests\Feature\Services;

use App\Models\Destination;
use App\Services\LlmRecommendationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class LlmRecommendationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.key' => 'fake-test-key',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.fallback_models' => 'gemini-test-fallback',
        ]);
    }

    public function test_it_maps_a_successful_gemini_response_to_real_destinations(): void
    {
        $destination = Destination::factory()->create(['is_active' => true]);

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => [
                        'parts' => [[
                            'text' => json_encode([
                                'recommendations' => [
                                    ['destination_id' => $destination->id, 'reason' => 'Cocok banget sama seleramu.'],
                                ],
                            ]),
                        ]],
                    ],
                ]],
            ], 200),
        ]);

        $result = (new LlmRecommendationService)->recommend(['interests' => ['Kuliner']]);

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]['destination']->is($destination));
        $this->assertSame('Cocok banget sama seleramu.', $result[0]['reason']);
    }

    public function test_it_ignores_destination_ids_that_do_not_exist(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode(['recommendations' => [
                            ['destination_id' => 999999, 'reason' => 'ID palsu, tidak ada di database.'],
                        ]]),
                    ]]],
                ]],
            ], 200),
        ]);

        $result = (new LlmRecommendationService)->recommend([]);

        $this->assertSame([], $result);
    }

    public function test_it_throws_when_api_key_is_missing(): void
    {
        config(['services.gemini.key' => null]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('GEMINI_API_KEY belum diatur');

        (new LlmRecommendationService)->recommend([]);
    }

    public function test_it_throws_a_friendly_error_when_gemini_keeps_failing(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response(['error' => 'server sibuk'], 503),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Server AI sedang sangat sibuk');

        (new LlmRecommendationService)->recommend([]);
    }

    public function test_it_falls_back_to_a_secondary_model_when_the_primary_keeps_failing(): void
    {
        $destination = Destination::factory()->create();

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-test:generateContent' => Http::response(['error' => 'server sibuk'], 503),
            'generativelanguage.googleapis.com/v1beta/models/gemini-test-fallback:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode(['recommendations' => [
                            ['destination_id' => $destination->id, 'reason' => 'Berhasil lewat model cadangan.'],
                        ]]),
                    ]]],
                ]],
            ], 200),
        ]);

        $result = (new LlmRecommendationService)->recommend([]);

        $this->assertCount(1, $result);
        $this->assertSame('Berhasil lewat model cadangan.', $result[0]['reason']);
    }

    public function test_it_falls_back_when_a_model_has_been_deprecated_by_google(): void
    {
        $destination = Destination::factory()->create();

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-test:generateContent' => Http::response([
                'error' => ['code' => 404, 'message' => 'model no longer available to new users'],
            ], 404),
            'generativelanguage.googleapis.com/v1beta/models/gemini-test-fallback:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode(['recommendations' => [
                            ['destination_id' => $destination->id, 'reason' => 'Model utama sudah dihentikan Google.'],
                        ]]),
                    ]]],
                ]],
            ], 200),
        ]);

        $result = (new LlmRecommendationService)->recommend([]);

        $this->assertCount(1, $result);
        $this->assertSame('Model utama sudah dihentikan Google.', $result[0]['reason']);
    }

    public function test_it_falls_back_when_the_primary_model_times_out(): void
    {
        $destination = Destination::factory()->create();

        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-test:generateContent' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
            'generativelanguage.googleapis.com/v1beta/models/gemini-test-fallback:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [[
                        'text' => json_encode(['recommendations' => [
                            ['destination_id' => $destination->id, 'reason' => 'Berhasil walau model utama timeout.'],
                        ]]),
                    ]]],
                ]],
            ], 200),
        ]);

        $result = (new LlmRecommendationService)->recommend([]);

        $this->assertCount(1, $result);
        $this->assertSame('Berhasil walau model utama timeout.', $result[0]['reason']);
    }

    public function test_it_throws_a_friendly_error_when_every_model_times_out(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => function () {
                throw new ConnectionException('cURL error 28: Operation timed out');
            },
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Server AI sedang sangat sibuk');

        (new LlmRecommendationService)->recommend([]);
    }

    public function test_it_does_not_try_fallback_models_on_a_non_capacity_error(): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/v1beta/models/gemini-test:generateContent' => Http::response(['error' => 'permintaan tidak valid'], 400),
            'generativelanguage.googleapis.com/v1beta/models/gemini-test-fallback:generateContent' => Http::response([
                'candidates' => [[
                    'content' => ['parts' => [['text' => '{"recommendations":[]}']]],
                ]],
            ], 200),
        ]);

        $this->expectException(RuntimeException::class);

        (new LlmRecommendationService)->recommend([]);
    }

    public function test_it_recovers_after_a_transient_failure_via_retry(): void
    {
        $destination = Destination::factory()->create();

        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::sequence()
                ->push(['error' => 'server sibuk'], 503)
                ->push([
                    'candidates' => [[
                        'content' => ['parts' => [[
                            'text' => json_encode(['recommendations' => [
                                ['destination_id' => $destination->id, 'reason' => 'Berhasil setelah retry.'],
                            ]]),
                        ]]],
                    ]],
                ], 200),
        ]);

        $result = (new LlmRecommendationService)->recommend([]);

        $this->assertCount(1, $result);
        $this->assertSame('Berhasil setelah retry.', $result[0]['reason']);
    }
}
