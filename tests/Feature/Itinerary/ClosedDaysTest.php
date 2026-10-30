<?php

namespace Tests\Feature\Itinerary;

use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\User;
use App\Services\ItineraryCostEstimator;
use App\Services\ItineraryInsights;
use App\Services\ScheduleValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ClosedDaysTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function itineraryWith(string $startDate, ?array $closedDays, int $day = 1): array
    {
        $user = User::factory()->create();
        $itinerary = Itinerary::factory()->create([
            'user_id' => $user->id,
            'start_date' => $startDate,
            'end_date' => Carbon::parse($startDate)->addDays(2)->toDateString(),
        ]);
        $destination = Destination::factory()->create(['closed_days' => $closedDays]);
        $item = $itinerary->items()->create([
            'destination_id' => $destination->id,
            'day_number' => $day,
            'start_time' => '09:00',
            'duration_minutes' => 60,
            'sort_order' => 0,
        ]);

        return [$user, $itinerary, $item];
    }

    public function test_warning_when_visit_falls_on_closed_day(): void
    {
        [$user, $itinerary] = $this->itineraryWith('2026-11-02', [1]);

        $this->actingAs($user)->get(route('itineraries.show', $itinerary))
            ->assertOk()
            ->assertSee('Tutup setiap hari Senin.');
    }

    public function test_warning_uses_correct_date_across_weeks(): void
    {
        // 2026-11-01 Minggu; hari ke-2 = Senin.
        [$user, $itinerary, $item] = $this->itineraryWith('2026-11-01', [1], 2);

        [, $warnings] = ItineraryInsights::build($itinerary->load('items.destination'));
        $this->assertSame(['Tutup setiap hari Senin.'], $warnings[2][$item->id]);

        $item->update(['day_number' => 1]);
        [, $warnings] = ItineraryInsights::build($itinerary->fresh()->load('items.destination'));
        $this->assertSame([], $warnings[1][$item->id]);
    }

    public function test_no_warning_when_closed_days_null(): void
    {
        [$user, $itinerary] = $this->itineraryWith('2026-11-02', null);

        $this->actingAs($user)->get(route('itineraries.show', $itinerary))
            ->assertOk()
            ->assertDontSee('Tutup setiap hari');
    }

    public function test_validate_day_without_date_has_no_closed_warning(): void
    {
        [, $itinerary, $item] = $this->itineraryWith('2026-11-02', [1]);
        $itinerary->load('items.destination');

        $warnings = ScheduleValidator::validateDay($itinerary->items);

        $this->assertSame([], $warnings[$item->id]);
    }

    public function test_pdf_includes_closed_day_warning(): void
    {
        [$user, $itinerary] = $this->itineraryWith('2026-11-02', [1]);

        $this->actingAs($user)->get(route('itineraries.pdf', $itinerary))->assertOk();

        $itinerary->load('items.destination');
        [, $warningsByDay, $travelInfoByDay] = ItineraryInsights::build($itinerary);
        $html = view('itineraries.pdf', [
            'itinerary' => $itinerary,
            'warningsByDay' => $warningsByDay,
            'travelInfoByDay' => $travelInfoByDay,
            'cost' => (new ItineraryCostEstimator)->estimate($itinerary),
        ])->render();

        $this->assertStringContainsString('Tutup setiap hari Senin.', $html);
    }
}
