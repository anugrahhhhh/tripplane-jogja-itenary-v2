<?php

namespace Tests\Feature\Quality;

use App\Models\Category;
use App\Models\Destination;
use App\Models\User;
use App\Support\FavoriteLookup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FavoriteLookupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Jumlah query ke tabel favorites saat membuka $url.
     */
    private function favoriteQueriesFor(string $url): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($url)->assertOk();
        $count = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains($q['query'], '"favorites"'))
            ->count();
        DB::disableQueryLog();

        return $count;
    }

    public function test_catalog_uses_one_favorite_query_regardless_of_card_count(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Destination::factory()->count(12)->for($category)->create();

        $this->actingAs($user);

        $this->assertSame(1, $this->favoriteQueriesFor('/katalog?q=a&urutkan=terbaru'));
        $this->assertSame(1, $this->favoriteQueriesFor('/kategori/'.$category->slug));
    }

    public function test_query_count_does_not_grow_with_more_cards(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        Destination::factory()->count(2)->for($category)->create();
        $this->actingAs($user);
        $few = $this->favoriteQueriesFor('/kategori/'.$category->slug);

        Destination::factory()->count(10)->for($category)->create();
        $many = $this->favoriteQueriesFor('/kategori/'.$category->slug);

        $this->assertSame($few, $many);
    }

    public function test_guest_does_not_query_favorites_at_all(): void
    {
        Destination::factory()->count(5)->create();

        $this->assertSame(0, $this->favoriteQueriesFor('/katalog?q=a'));
    }

    public function test_cards_show_correct_favorite_state(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $liked = Destination::factory()->for($category)->create(['name' => 'Disukai']);
        $other = Destination::factory()->for($category)->create(['name' => 'Biasa']);
        $user->favorites()->create(['destination_id' => $liked->id]);

        $html = $this->actingAs($user)->get('/kategori/'.$category->slug)->assertOk()->getContent();

        $this->assertStringContainsString('action="'.route('favorites.destroy', $liked).'"', $html);
        $this->assertStringContainsString('action="'.route('favorites.store', $other).'"', $html);
    }

    public function test_favorite_state_updates_on_the_next_request(): void
    {
        $user = User::factory()->create();
        $destination = Destination::factory()->create();
        $this->actingAs($user);

        $this->get(route('destinations.show', $destination))->assertOk();
        $this->assertFalse($destination->isFavoritedBy($user));

        $this->post(route('favorites.store', $destination))->assertRedirect();
        $this->assertTrue($destination->isFavoritedBy($user));

        $this->delete(route('favorites.destroy', $destination))->assertRedirect();
        $this->assertFalse($destination->isFavoritedBy($user));
    }

    public function test_change_within_the_same_request_is_reflected(): void
    {
        $user = User::factory()->create();
        $destination = Destination::factory()->create();

        $this->assertFalse(FavoriteLookup::has($user, $destination->id));

        $favorite = $user->favorites()->create(['destination_id' => $destination->id]);
        $this->assertTrue(FavoriteLookup::has($user, $destination->id));

        $favorite->delete();
        $this->assertFalse(FavoriteLookup::has($user, $destination->id));
    }

    public function test_forget_refreshes_after_bulk_delete_that_skips_model_events(): void
    {
        $user = User::factory()->create();
        $destination = Destination::factory()->create();
        $user->favorites()->create(['destination_id' => $destination->id]);
        $this->assertTrue(FavoriteLookup::has($user, $destination->id));

        // Hapus lewat query builder (seperti FavoriteController::destroy):
        // event model tidak jalan, jadi harus forget() manual.
        $user->favorites()->where('destination_id', $destination->id)->delete();
        FavoriteLookup::forget($user);

        $this->assertFalse(FavoriteLookup::has($user, $destination->id));
    }

    public function test_forget_without_argument_clears_every_user(): void
    {
        $alice = User::factory()->create();
        $budi = User::factory()->create();
        $destination = Destination::factory()->create();
        $alice->favorites()->create(['destination_id' => $destination->id]);
        $budi->favorites()->create(['destination_id' => $destination->id]);
        FavoriteLookup::has($alice, $destination->id);
        FavoriteLookup::has($budi, $destination->id);

        DB::table('favorites')->delete();
        FavoriteLookup::forget();

        $this->assertFalse(FavoriteLookup::has($alice, $destination->id));
        $this->assertFalse(FavoriteLookup::has($budi, $destination->id));
    }

    public function test_lookup_is_per_user(): void
    {
        $alice = User::factory()->create();
        $budi = User::factory()->create();
        $destination = Destination::factory()->create();
        $alice->favorites()->create(['destination_id' => $destination->id]);

        $this->assertTrue(FavoriteLookup::has($alice, $destination->id));
        $this->assertFalse(FavoriteLookup::has($budi, $destination->id));
    }

    public function test_handles_missing_user_or_destination_safely(): void
    {
        $this->assertFalse(FavoriteLookup::has(null, 1));
        $this->assertFalse(FavoriteLookup::has(User::factory()->create(), null));
        $this->assertFalse(FavoriteLookup::has(new User, 1));
    }

    public function test_cards_without_reviews_do_not_query_ratings_again(): void
    {
        $category = Category::factory()->create();
        Destination::factory()->count(6)->for($category)->create();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get('/kategori/'.$category->slug)->assertOk()->assertSee('Baru');
        $reviewQueries = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_starts_with(strtolower(ltrim($q['query'])), 'select avg'))
            ->count();
        DB::disableQueryLog();

        $this->assertSame(0, $reviewQueries);
    }
}
