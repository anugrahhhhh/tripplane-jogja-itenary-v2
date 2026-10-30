<?php

namespace Tests\Feature\Itinerary;

use App\Models\Destination;
use App\Models\Itinerary;
use App\Services\ItineraryCostEstimator;
use App\Services\ItineraryInsights;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CostDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function makeItinerary(int $travelers, array $prices): Itinerary
    {
        $itinerary = Itinerary::factory()->create(['traveler_count' => $travelers]);

        foreach ($prices as $i => [$min, $max]) {
            $destination = Destination::factory()->create(['price_min' => $min, 'price_max' => $max]);
            $itinerary->items()->create([
                'destination_id' => $destination->id,
                'day_number' => 1,
                'start_time' => sprintf('%02d:00', 9 + $i * 2),
                'duration_minutes' => 60,
                'sort_order' => $i,
            ]);
        }

        return $itinerary;
    }

    public function test_show_page_displays_cost_per_person_and_group(): void
    {
        $itinerary = $this->makeItinerary(4, [[20000, 50000], [30000, 70000]]);

        $this->actingAs($itinerary->user)
            ->get(route('itineraries.show', $itinerary))
            ->assertOk()
            ->assertSee('Estimasi biaya')
            ->assertSee('Rp 50.000 – Rp 120.000 per orang')
            ->assertSee('Rp 200.000 – Rp 480.000 untuk 4 orang')
            ->assertSee('Hari 1: Rp 50.000 – Rp 120.000')
            ->assertSee('Estimasi tiket masuk saja, belum termasuk transportasi, makan, dan parkir.');
    }

    public function test_single_traveler_hides_group_total(): void
    {
        $itinerary = $this->makeItinerary(1, [[20000, 50000]]);

        $this->actingAs($itinerary->user)
            ->get(route('itineraries.show', $itinerary))
            ->assertOk()
            ->assertSee('Rp 20.000 – Rp 50.000 per orang')
            ->assertDontSee('untuk 1 orang');
    }

    public function test_free_itinerary_shows_gratis(): void
    {
        $itinerary = $this->makeItinerary(3, [[0, 0]]);

        $this->actingAs($itinerary->user)
            ->get(route('itineraries.show', $itinerary))
            ->assertOk()
            ->assertSee('Gratis')
            ->assertDontSee('Gratis per orang')
            ->assertDontSee('untuk 3 orang');
    }

    public function test_empty_itinerary_page_loads(): void
    {
        $itinerary = Itinerary::factory()->create(['traveler_count' => 2]);

        $this->actingAs($itinerary->user)
            ->get(route('itineraries.show', $itinerary))
            ->assertOk()
            ->assertSee('Estimasi biaya')
            ->assertSee('Gratis');
    }

    public function test_pdf_view_contains_cost(): void
    {
        $itinerary = $this->makeItinerary(2, [[20000, 50000]]);
        $itinerary->load('items.destination');

        [, $warningsByDay, $travelInfoByDay] = ItineraryInsights::build($itinerary);

        $html = view('itineraries.pdf', [
            'itinerary' => $itinerary,
            'warningsByDay' => $warningsByDay,
            'travelInfoByDay' => $travelInfoByDay,
            'cost' => (new ItineraryCostEstimator)->estimate($itinerary),
        ])->render();

        $this->assertStringContainsString('Estimasi biaya', $html);
        $this->assertStringContainsString('Rp 20.000 – Rp 50.000 per orang', $html);
        $this->assertStringContainsString('untuk 2 orang', $html);

        $this->actingAs($itinerary->user)
            ->get(route('itineraries.pdf', $itinerary))
            ->assertOk();
    }
}
