<?php

namespace Tests\Feature\Quality;

use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Destinasi yang dinonaktifkan admin tidak boleh bisa difavoritkan, diulas,
 * atau ditambahkan ke itinerary lewat request langsung.
 */
class InactiveDestinationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cannot_review_inactive_destination(): void
    {
        $destination = Destination::factory()->create(['is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->post(route('reviews.store', $destination), ['rating' => 5, 'comment' => 'x'])
            ->assertNotFound();

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_can_still_review_active_destination(): void
    {
        $destination = Destination::factory()->create();

        $this->actingAs(User::factory()->create())
            ->post(route('reviews.store', $destination), ['rating' => 5])
            ->assertRedirect();

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_cannot_favorite_inactive_destination(): void
    {
        $destination = Destination::factory()->create(['is_active' => false]);

        $this->actingAs(User::factory()->create())
            ->post(route('favorites.store', $destination))
            ->assertNotFound();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_can_still_remove_favorite_of_deactivated_destination(): void
    {
        $user = User::factory()->create();
        $destination = Destination::factory()->create();
        $user->favorites()->create(['destination_id' => $destination->id]);
        $destination->update(['is_active' => false]);

        $this->actingAs($user)->delete(route('favorites.destroy', $destination))->assertRedirect();

        $this->assertDatabaseCount('favorites', 0);
    }

    public function test_favorites_page_hides_deactivated_destination_and_shows_it_again_when_reactivated(): void
    {
        $user = User::factory()->create();
        $destination = Destination::factory()->create(['name' => 'Pantai Sementara Tutup']);
        $user->favorites()->create(['destination_id' => $destination->id]);

        $destination->update(['is_active' => false]);
        $this->actingAs($user)->get(route('favorites.index'))->assertOk()->assertDontSee('Pantai Sementara Tutup');

        $destination->update(['is_active' => true]);
        $this->get(route('favorites.index'))->assertSee('Pantai Sementara Tutup');
    }

    public function test_cannot_add_inactive_destination_to_itinerary(): void
    {
        $itinerary = Itinerary::factory()->create();
        $destination = Destination::factory()->create(['is_active' => false]);

        $this->actingAs($itinerary->user)
            ->from(route('itineraries.show', $itinerary))
            ->post(route('itinerary-items.store', $itinerary), [
                'destination_id' => $destination->id,
                'day_number' => 1,
                'start_time' => '09:00',
                'duration_minutes' => 60,
            ])
            ->assertSessionHasErrors(['destination_id' => 'Destinasi yang dipilih tidak tersedia.']);

        $this->assertSame(0, $itinerary->items()->count());
    }

    public function test_can_still_add_active_destination_to_itinerary(): void
    {
        $itinerary = Itinerary::factory()->create();
        $destination = Destination::factory()->create();

        $this->actingAs($itinerary->user)
            ->post(route('itinerary-items.store', $itinerary), [
                'destination_id' => $destination->id,
                'day_number' => 1,
                'start_time' => '09:00',
                'duration_minutes' => 60,
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(1, $itinerary->items()->count());
    }
}
