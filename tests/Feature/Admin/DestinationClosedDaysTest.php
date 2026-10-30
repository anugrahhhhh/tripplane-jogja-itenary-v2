<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Destination;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DestinationClosedDaysTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function payload(Destination $destination, array $overrides = []): array
    {
        return array_merge([
            'category_id' => $destination->category_id,
            'name' => $destination->name,
            'description' => 'Deskripsi tes.',
            'address' => 'Alamat tes',
            'latitude' => -7.8,
            'longitude' => 110.36,
            'price_min' => 0,
            'price_max' => 0,
            'open_time' => '08:00',
            'close_time' => '17:00',
            'is_active' => '1',
        ], $overrides);
    }

    private function destination(array $attrs = []): Destination
    {
        return Destination::factory()->create(array_merge(
            ['category_id' => Category::factory()->create()->id],
            $attrs
        ));
    }

    public function test_admin_can_save_closed_days_sorted(): void
    {
        $destination = $this->destination();

        $this->actingAs($this->admin())
            ->put(route('admin.destinations.update', $destination), $this->payload($destination, ['closed_days' => [3, 1]]))
            ->assertSessionHasNoErrors();

        $this->assertSame([1, 3], $destination->fresh()->closed_days);
    }

    public function test_no_checkbox_saves_null(): void
    {
        $destination = $this->destination(['closed_days' => [2]]);

        $this->actingAs($this->admin())
            ->put(route('admin.destinations.update', $destination), $this->payload($destination))
            ->assertSessionHasNoErrors();

        $this->assertNull($destination->fresh()->closed_days);
    }

    public function test_invalid_closed_days_rejected(): void
    {
        $destination = $this->destination();

        foreach ([[0], [8], [1, 1]] as $value) {
            $this->actingAs($this->admin())
                ->put(route('admin.destinations.update', $destination), $this->payload($destination, ['closed_days' => $value]))
                ->assertSessionHasErrors('closed_days.*');
        }

        $this->assertNull($destination->fresh()->closed_days);
    }

    public function test_detail_page_shows_closed_days(): void
    {
        $destination = $this->destination(['closed_days' => [1]]);

        $this->get(route('destinations.show', $destination))->assertSee('Tutup: Senin');
    }

    public function test_detail_page_hides_closed_line_when_none(): void
    {
        $destination = $this->destination();

        $this->get(route('destinations.show', $destination))->assertDontSee('Tutup:');
    }
}
