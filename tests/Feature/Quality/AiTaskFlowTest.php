<?php

namespace Tests\Feature\Quality;

use App\Jobs\ProcessAiTask;
use App\Models\AiTask;
use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Responsiveness: permintaan AI diproses di queue dan pengguna memantau
 * progresnya, bukan menunggu satu request panjang.
 */
class AiTaskFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.gemini.key' => 'fake-test-key',
            'services.gemini.model' => 'gemini-test',
            'services.gemini.fallback_models' => '',
        ]);
    }

    private function fakeGemini(array $payload): void
    {
        Http::fake([
            'generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [['content' => ['parts' => [['text' => json_encode($payload)]]]]],
            ], 200),
        ]);
    }

    /** Jalankan job seperti worker (Queue::fake juga menahan dispatchSync). */
    private function runWorker(AiTask $task): void
    {
        app()->call([new ProcessAiTask($task), 'handle']);
    }

    private function itineraryForm(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Liburan AI',
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(3)->toDateString(),
            'notes' => 'santai',
        ], $overrides);
    }

    public function test_ai_itinerary_request_is_queued_on_the_ai_queue_and_user_sees_progress_page(): void
    {
        Queue::fake();
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('itineraries.ai-store'), $this->itineraryForm());

        $task = AiTask::sole();
        $response->assertRedirect(route('ai-tasks.show', $task));
        $this->assertSame(AiTask::STATUS_QUEUED, $task->status);
        $this->assertSame($user->id, $task->user_id);
        Queue::assertPushedOn('ai', ProcessAiTask::class);

        $this->actingAs($user)->get(route('ai-tasks.show', $task))
            ->assertOk()
            ->assertSee('AI sedang menyusun itinerary-mu');

        $this->actingAs($user)->getJson(route('ai-tasks.status', $task))
            ->assertOk()
            ->assertJson(['status' => 'queued', 'redirect' => null]);
    }

    public function test_worker_builds_the_itinerary_and_status_points_to_it(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $destination = Destination::factory()->create(['is_active' => true]);

        $this->actingAs($user)->post(route('itineraries.ai-store'), $this->itineraryForm());
        $task = AiTask::sole();

        $this->fakeGemini(['days' => [['day_number' => 1, 'items' => [
            ['destination_id' => $destination->id, 'start_time' => '09:00', 'duration_minutes' => 90, 'note' => 'Pagi masih sejuk.'],
        ]]]]);

        $this->runWorker($task);

        $task->refresh();
        $itinerary = Itinerary::sole();
        $this->assertSame(AiTask::STATUS_COMPLETED, $task->status);
        $this->assertSame($itinerary->id, $task->itinerary_id);
        $this->assertSame('Liburan AI', $itinerary->title);
        $this->assertSame(1, $itinerary->items()->count());

        $this->actingAs($user)->getJson(route('ai-tasks.status', $task))
            ->assertJson(['status' => 'completed', 'redirect' => route('itineraries.show', $itinerary)]);

        $this->actingAs($user)->get(route('ai-tasks.show', $task))
            ->assertRedirect(route('itineraries.show', $itinerary));
    }

    public function test_with_sync_queue_user_goes_straight_to_the_result(): void
    {
        $user = User::factory()->create();
        $destination = Destination::factory()->create(['is_active' => true]);
        $this->fakeGemini(['days' => [['day_number' => 1, 'items' => [
            ['destination_id' => $destination->id, 'start_time' => '10:00', 'duration_minutes' => 60, 'note' => 'Cocok.'],
        ]]]]);

        $response = $this->actingAs($user)->post(route('itineraries.ai-store'), $this->itineraryForm());

        $response->assertRedirect(route('itineraries.show', Itinerary::sole()));
        $response->assertSessionHas('status');
    }

    public function test_failed_ai_returns_to_the_form_with_previous_input_and_message(): void
    {
        $user = User::factory()->create();
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response([], 500)]);

        $response = $this->actingAs($user)
            ->from(route('itineraries.ai-create'))
            ->post(route('itineraries.ai-store'), $this->itineraryForm(['notes' => 'wisata sejarah']));

        $response->assertRedirect(route('itineraries.ai-create'));
        $response->assertSessionHas('error');
        $response->assertSessionHasInput('notes', 'wisata sejarah');
        $this->assertSame(AiTask::STATUS_FAILED, AiTask::sole()->status);
        $this->assertSame(0, Itinerary::count());
    }

    public function test_failed_task_can_be_retried_with_the_same_input(): void
    {
        $user = User::factory()->create();
        $destination = Destination::factory()->create(['is_active' => true]);
        $task = AiTask::create([
            'user_id' => $user->id,
            'type' => AiTask::TYPE_ITINERARY,
            'status' => AiTask::STATUS_FAILED,
            'error' => 'Server AI sibuk.',
            'input' => $this->itineraryForm(),
        ]);

        $this->actingAs($user)->get(route('ai-tasks.show', $task))
            ->assertOk()
            ->assertSee('Server AI sibuk.')
            ->assertSee('Coba lagi');

        $this->fakeGemini(['days' => [['day_number' => 1, 'items' => [
            ['destination_id' => $destination->id, 'start_time' => '09:00', 'duration_minutes' => 60, 'note' => 'Oke.'],
        ]]]]);

        $this->actingAs($user)->post(route('ai-tasks.retry', $task))
            ->assertRedirect(route('itineraries.show', Itinerary::sole()));

        $this->assertSame(AiTask::STATUS_COMPLETED, $task->fresh()->status);
    }

    public function test_edit_link_returns_to_the_form_with_the_task_input(): void
    {
        $user = User::factory()->create();
        $task = AiTask::create([
            'user_id' => $user->id,
            'type' => AiTask::TYPE_ITINERARY,
            'status' => AiTask::STATUS_FAILED,
            'input' => $this->itineraryForm(['notes' => 'kuliner malam']),
        ]);

        $this->actingAs($user)->get(route('ai-tasks.edit', $task))
            ->assertRedirect(route('itineraries.ai-create'))
            ->assertSessionHasInput('notes', 'kuliner malam');
    }

    public function test_task_stuck_in_queue_is_reported_as_failed_instead_of_waiting_forever(): void
    {
        $user = User::factory()->create();
        $task = AiTask::create([
            'user_id' => $user->id,
            'type' => AiTask::TYPE_ITINERARY,
            'status' => AiTask::STATUS_QUEUED,
            'input' => $this->itineraryForm(),
        ]);
        $task->forceFill(['created_at' => now()->subSeconds(AiTask::MAX_QUEUE_WAIT + 5)])->save();

        $this->actingAs($user)->getJson(route('ai-tasks.status', $task))
            ->assertJson(['status' => 'failed'])
            ->assertJsonPath('error', fn ($error) => str_contains($error, 'coba lagi'));
    }

    public function test_tasks_of_other_users_are_not_visible(): void
    {
        $owner = User::factory()->create();
        $task = AiTask::create([
            'user_id' => $owner->id,
            'type' => AiTask::TYPE_ITINERARY,
            'status' => AiTask::STATUS_QUEUED,
            'input' => $this->itineraryForm(),
        ]);

        $this->actingAs(User::factory()->create())->get(route('ai-tasks.show', $task))->assertNotFound();
        $this->actingAs(User::factory()->create())->getJson(route('ai-tasks.status', $task))->assertNotFound();
        $this->post(route('ai-tasks.retry', $task))->assertNotFound();
    }

    public function test_guest_recommendation_is_processed_and_result_is_shown_to_the_same_session(): void
    {
        Queue::fake();
        $destination = Destination::factory()->create(['is_active' => true, 'name' => 'Taman Sari Uji']);

        $this->post(route('recommendations.store'), ['duration' => 'singkat']);
        $task = AiTask::sole();
        $sessionCookie = [config('session.cookie') => $this->app['session']->getId()];

        $this->assertNull($task->user_id);
        $this->assertSame('1-2 jam', $task->input['duration_label']);

        $this->fakeGemini(['recommendations' => [
            ['destination_id' => $destination->id, 'reason' => 'Pas untuk kunjungan singkat.'],
        ]]);
        $this->runWorker($task);

        $this->withCookies($sessionCookie)->withCredentials()->getJson(route('ai-tasks.status', $task))
            ->assertJson(['status' => 'completed', 'redirect' => route('ai-tasks.result', $task)]);

        $this->withCookies($sessionCookie)->get(route('ai-tasks.result', $task))
            ->assertOk()
            ->assertSee('Taman Sari Uji')
            ->assertSee('Pas untuk kunjungan singkat.');

        // Sesi lain (tamu lain) tidak bisa melihat hasilnya.
        $this->withCookies([config('session.cookie') => str_repeat('x', 40)])
            ->get(route('ai-tasks.result', $task))
            ->assertNotFound();
    }

    public function test_old_tasks_are_pruned(): void
    {
        $old = AiTask::create(['type' => AiTask::TYPE_RECOMMENDATION, 'input' => [], 'guest_token' => str_repeat('a', 64)]);
        $old->forceFill(['created_at' => now()->subDays(3)])->save();
        $fresh = AiTask::create(['type' => AiTask::TYPE_RECOMMENDATION, 'input' => [], 'guest_token' => str_repeat('b', 64)]);

        $this->artisan('model:prune', ['--model' => [AiTask::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($fresh);
    }
}
