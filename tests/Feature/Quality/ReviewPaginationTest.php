<?php

namespace Tests\Feature\Quality;

use App\Models\Destination;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewPaginationTest extends TestCase
{
    use RefreshDatabase;

    private function destinationWithReviews(int $visible, int $hidden = 0): Destination
    {
        $destination = Destination::factory()->create();

        foreach (range(1, $visible + $hidden) as $i) {
            Review::create([
                'user_id' => User::factory()->create(['name' => sprintf('Pengulas %02d', $i)])->id,
                'destination_id' => $destination->id,
                'rating' => 4,
                'comment' => sprintf('Komentar nomor %02d', $i),
                'is_visible' => $i <= $visible,
            ])->forceFill(['created_at' => now()->subMinutes(100 - $i)])->save();
        }

        return $destination;
    }

    public function test_first_page_shows_ten_newest_reviews(): void
    {
        $destination = $this->destinationWithReviews(15);

        $response = $this->get(route('destinations.show', $destination))->assertOk();

        $response->assertSee('Komentar nomor 15')->assertSee('Komentar nomor 06');
        $response->assertDontSee('Komentar nomor 05');
        $response->assertSee('Ulasan (15)');
        $response->assertSee('ulasan=2#ulasan', false);
    }

    public function test_second_page_shows_the_rest(): void
    {
        $destination = $this->destinationWithReviews(15);

        $this->get(route('destinations.show', $destination).'?ulasan=2')
            ->assertOk()
            ->assertSee('Komentar nomor 05')
            ->assertSee('Komentar nomor 01')
            ->assertDontSee('Komentar nomor 06');
    }

    public function test_hidden_reviews_are_not_shown_or_counted(): void
    {
        $destination = $this->destinationWithReviews(3, 2);

        $this->get(route('destinations.show', $destination))
            ->assertSee('Ulasan (3)')
            ->assertDontSee('Komentar nomor 04')
            ->assertDontSee('ulasan=2', false);
    }

    public function test_no_reviews_shows_empty_state(): void
    {
        $destination = Destination::factory()->create();

        $this->get(route('destinations.show', $destination))
            ->assertOk()
            ->assertSee('Belum ada ulasan. Jadilah yang pertama!');
    }

    public function test_out_of_range_page_is_safe(): void
    {
        $destination = $this->destinationWithReviews(3);

        $this->get(route('destinations.show', $destination).'?ulasan=999')->assertOk();
        $this->get(route('destinations.show', $destination).'?ulasan=abc')->assertOk();
    }
}
