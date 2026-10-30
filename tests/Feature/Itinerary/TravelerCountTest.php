<?php

namespace Tests\Feature\Itinerary;

use App\Models\Itinerary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class TravelerCountTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'title' => 'Liburan Jogja',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'traveler_count' => 3,
        ], $override);
    }

    public function test_store_saves_traveler_count(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('itineraries.store'), $this->payload())->assertRedirect();

        $this->assertDatabaseHas('itineraries', ['user_id' => $user->id, 'traveler_count' => 3]);
    }

    public function test_traveler_count_out_of_range_rejected(): void
    {
        $user = User::factory()->create();

        foreach ([0, 51] as $value) {
            $this->actingAs($user)
                ->post(route('itineraries.store'), $this->payload(['traveler_count' => $value]))
                ->assertSessionHasErrors(['traveler_count' => 'Jumlah peserta harus antara 1 dan 50.']);
        }

        $this->assertDatabaseCount('itineraries', 0);
    }

    public function test_update_changes_traveler_count(): void
    {
        $itinerary = Itinerary::factory()->create(['traveler_count' => 1]);

        $this->actingAs($itinerary->user)
            ->put(route('itineraries.update', $itinerary), $this->payload(['traveler_count' => 7]))
            ->assertRedirect();

        $this->assertSame(7, $itinerary->fresh()->traveler_count);
    }

    public function test_existing_itineraries_default_to_one(): void
    {
        $user = User::factory()->create();

        $id = DB::table('itineraries')->insertGetId([
            'user_id' => $user->id,
            'title' => 'Lama',
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'type' => 'planned',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertSame(1, Itinerary::findOrFail($id)->traveler_count);
    }
}
