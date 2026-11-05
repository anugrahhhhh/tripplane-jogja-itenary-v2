<?php

namespace Tests\Feature\Itinerary;

use App\Models\Destination;
use App\Models\Itinerary;
use App\Services\ItineraryCostEstimator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItineraryCostEstimatorTest extends TestCase
{
    use RefreshDatabase;

    private function addItem(Itinerary $itinerary, int $day, int $min, int $max, ?Destination $destination = null): Destination
    {
        $destination ??= Destination::factory()->create(['price_min' => $min, 'price_max' => $max]);

        $itinerary->items()->create([
            'destination_id' => $destination->id,
            'day_number' => $day,
            'start_time' => '09:00',
            'duration_minutes' => 60,
            'sort_order' => $itinerary->items()->count(),
        ]);

        return $destination;
    }

    public function test_sums_per_day_and_total(): void
    {
        $itinerary = Itinerary::factory()->create();
        $this->addItem($itinerary, 1, 10000, 20000);
        $this->addItem($itinerary, 1, 0, 0);
        $this->addItem($itinerary, 2, 5000, 5000);

        $result = (new ItineraryCostEstimator)->estimate($itinerary);

        $this->assertSame(['min' => 10000, 'max' => 20000], $result['days'][1]);
        $this->assertSame(['min' => 5000, 'max' => 5000], $result['days'][2]);
        $this->assertSame(['min' => 15000, 'max' => 25000], $result['total']);
    }

    public function test_same_destination_twice_counts_twice(): void
    {
        $itinerary = Itinerary::factory()->create();
        $destination = $this->addItem($itinerary, 1, 10000, 20000);
        $this->addItem($itinerary, 2, 10000, 20000, $destination);

        $result = (new ItineraryCostEstimator)->estimate($itinerary);

        $this->assertSame(['min' => 20000, 'max' => 40000], $result['total']);
    }

    public function test_group_total_multiplies_by_travelers(): void
    {
        $itinerary = Itinerary::factory()->create(['traveler_count' => 4]);
        $this->addItem($itinerary, 1, 10000, 20000);

        $result = (new ItineraryCostEstimator)->estimate($itinerary);

        $this->assertSame(4, $result['travelers']);
        $this->assertSame(['min' => 40000, 'max' => 80000], $result['group_total']);
    }

    public function test_empty_itinerary_has_zero_total(): void
    {
        $itinerary = Itinerary::factory()->create();

        $result = (new ItineraryCostEstimator)->estimate($itinerary);

        $this->assertSame([], $result['days']);
        $this->assertSame(['min' => 0, 'max' => 0], $result['total']);
        $this->assertSame(['min' => 0, 'max' => 0], $result['group_total']);
    }
}
