<?php

namespace Tests\Feature\Itinerary;

use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareLinkTest extends TestCase
{
    use RefreshDatabase;

    private function sharedItinerary(): Itinerary
    {
        $owner = User::factory()->create(['name' => 'Pemilik Rahasia', 'email' => 'pemilik.rahasia@example.test']);
        $itinerary = Itinerary::factory()->for($owner)->create(['title' => 'Liburan Publik Jogja']);
        $destination = Destination::factory()->create(['name' => 'Tamu Candi Unik', 'price_min' => 10000, 'price_max' => 20000]);
        $itinerary->items()->create([
            'destination_id' => $destination->id,
            'day_number' => 1,
            'start_time' => '09:00',
            'duration_minutes' => 60,
            'sort_order' => 0,
            'notes' => 'catatan pribadi sangat rahasia',
        ]);
        $itinerary->forceFill(['share_token' => str_repeat('a', 40)])->save();

        return $itinerary;
    }

    public function test_shared_page_has_itinerary_title(): void
    {
        $itinerary = $this->sharedItinerary();

        $this->get(route('itineraries.shared', $itinerary->share_token))
            ->assertOk()
            ->assertSee('<title>Liburan Publik Jogja - ', false);
    }

    public function test_owner_can_create_share_link(): void
    {
        $itinerary = Itinerary::factory()->create();

        $this->actingAs($itinerary->user)
            ->post(route('itineraries.share.store', $itinerary))
            ->assertRedirect(route('itineraries.show', $itinerary))
            ->assertSessionHas('status', 'Link berbagi dibuat.');

        $this->assertSame(40, strlen($itinerary->fresh()->share_token));
    }

    public function test_regenerating_invalidates_old_link(): void
    {
        $itinerary = $this->sharedItinerary();
        $old = $itinerary->share_token;

        $this->actingAs($itinerary->user)->post(route('itineraries.share.store', $itinerary));
        $new = $itinerary->fresh()->share_token;

        $this->assertNotSame($old, $new);
        $this->get(route('itineraries.shared', $old))->assertNotFound();
        $this->get(route('itineraries.shared', $new))->assertOk();
    }

    public function test_owner_can_disable_share_link(): void
    {
        $itinerary = $this->sharedItinerary();
        $old = $itinerary->share_token;

        $this->actingAs($itinerary->user)
            ->delete(route('itineraries.share.destroy', $itinerary))
            ->assertRedirect(route('itineraries.show', $itinerary))
            ->assertSessionHas('status', 'Link berbagi dimatikan.');

        $this->assertNull($itinerary->fresh()->share_token);
        $this->get(route('itineraries.shared', $old))->assertNotFound();
    }

    public function test_collaborator_and_stranger_cannot_manage_link(): void
    {
        $itinerary = $this->sharedItinerary();
        $collaborator = User::factory()->create();
        $itinerary->collaborators()->attach($collaborator->id);
        $stranger = User::factory()->create();

        foreach ([$collaborator, $stranger] as $user) {
            $this->actingAs($user)->post(route('itineraries.share.store', $itinerary))->assertForbidden();
            $this->actingAs($user)->delete(route('itineraries.share.destroy', $itinerary))->assertForbidden();
        }

        $this->assertSame(str_repeat('a', 40), $itinerary->fresh()->share_token);
    }

    public function test_collaborator_does_not_see_share_controls(): void
    {
        $itinerary = $this->sharedItinerary();
        $collaborator = User::factory()->create();
        $itinerary->collaborators()->attach($collaborator->id);

        $this->actingAs($collaborator)
            ->get(route('itineraries.show', $itinerary))
            ->assertOk()
            ->assertDontSee('Bagikan itinerary')
            ->assertDontSee($itinerary->share_token);

        $this->actingAs($itinerary->user)
            ->get(route('itineraries.show', $itinerary))
            ->assertSee('Bagikan itinerary')
            ->assertSee(route('itineraries.shared', $itinerary->share_token));
    }

    public function test_guest_can_view_shared_itinerary(): void
    {
        $itinerary = $this->sharedItinerary();

        $this->get(route('itineraries.shared', $itinerary->share_token))
            ->assertOk()
            ->assertSee('Liburan Publik Jogja')
            ->assertSee('Tamu Candi Unik')
            ->assertSee('Estimasi biaya');
    }

    public function test_shared_page_hides_private_data(): void
    {
        $itinerary = $this->sharedItinerary();
        $collaborator = User::factory()->create(['name' => 'Kolaborator Tersembunyi']);
        $itinerary->collaborators()->attach($collaborator->id);

        $this->get(route('itineraries.shared', $itinerary->share_token))
            ->assertOk()
            ->assertDontSee('Pemilik Rahasia')
            ->assertDontSee('pemilik.rahasia@example.test')
            ->assertDontSee('Kolaborator Tersembunyi')
            ->assertDontSee('catatan pribadi sangat rahasia');
    }

    public function test_shared_page_has_noindex_header(): void
    {
        $itinerary = $this->sharedItinerary();

        $this->get(route('itineraries.shared', $itinerary->share_token))
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_unknown_token_is_404(): void
    {
        $this->get(route('itineraries.shared', str_repeat('z', 40)))->assertNotFound();
        $this->get(route('itineraries.shared', 'pendek'))->assertNotFound();
    }

    public function test_shared_page_with_no_items_loads(): void
    {
        $itinerary = Itinerary::factory()->create(['title' => 'Kosong Dulu']);
        $itinerary->forceFill(['share_token' => str_repeat('b', 40)])->save();

        $this->get(route('itineraries.shared', $itinerary->share_token))
            ->assertOk()
            ->assertSee('Kosong Dulu');
    }

    public function test_share_token_is_not_mass_assignable(): void
    {
        $itinerary = Itinerary::create([
            'user_id' => User::factory()->create()->id,
            'title' => 'X',
            'start_date' => now(),
            'end_date' => now(),
            'share_token' => 'x',
        ]);

        $this->assertNull($itinerary->fresh()->share_token);
    }

    public function test_user_without_two_factor_can_open_share_link(): void
    {
        $itinerary = $this->sharedItinerary();

        $this->actingAs(User::factory()->withoutTwoFactor()->create())
            ->get(route('itineraries.shared', $itinerary->share_token))
            ->assertOk();
    }
}
