<?php

namespace Tests\Feature\Quality;

use App\Models\Category;
use App\Models\Destination;
use App\Models\Review;
use App\Models\User;
use App\Support\CatalogCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class CatalogCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_is_served_from_cache_on_second_visit(): void
    {
        Destination::factory()->create(['name' => 'Taman Sari']);

        $this->get('/')->assertOk()->assertSee('Taman Sari');
        $this->assertTrue(Cache::has(CatalogCache::HOME));

        DB::enableQueryLog();
        $this->get('/')->assertOk()->assertSee('Taman Sari');
        $queries = collect(DB::getQueryLog())->pluck('query')->implode("\n");
        DB::disableQueryLog();

        $this->assertStringNotContainsString('from "categories"', $queries);
    }

    public function test_new_destination_appears_immediately(): void
    {
        $category = Category::factory()->create();
        Destination::factory()->for($category)->create(['name' => 'Taman Sari']);

        $this->get('/')->assertSee('Taman Sari');
        $this->get('/katalog')->assertSee('Taman Sari');

        Destination::factory()->for($category)->create(['name' => 'Malioboro Baru']);

        $this->get('/')->assertSee('Malioboro Baru');
        $this->get('/katalog')->assertSee('Malioboro Baru');
    }

    public function test_deactivated_destination_disappears_immediately(): void
    {
        $destination = Destination::factory()->create(['name' => 'Kotagede Lama']);
        $this->get('/katalog')->assertSee('Kotagede Lama');
        $this->get('/')->assertSee('Kotagede Lama');

        $destination->update(['is_active' => false]);

        $this->get('/katalog')->assertDontSee('Kotagede Lama');
        $this->get('/')->assertDontSee('Kotagede Lama');
    }

    public function test_renamed_category_updates_immediately(): void
    {
        $category = Category::factory()->create(['name' => 'Wisata Lama']);
        Destination::factory()->for($category)->create();
        $this->get('/katalog')->assertSee('Wisata Lama');

        $category->update(['name' => 'Wisata Budaya Baru']);

        $this->get('/katalog')->assertSee('Wisata Budaya Baru')->assertDontSee('Wisata Lama');
    }

    public function test_review_change_clears_cache(): void
    {
        $destination = Destination::factory()->create();
        $this->get('/');
        $this->assertTrue(Cache::has(CatalogCache::HOME));

        Review::create([
            'user_id' => User::factory()->create()->id,
            'destination_id' => $destination->id,
            'rating' => 5,
            'comment' => 'Bagus',
            'is_visible' => true,
        ]);

        $this->assertFalse(Cache::has(CatalogCache::HOME));
    }

    public function test_search_results_stay_correct_with_cached_categories(): void
    {
        Destination::factory()->create(['name' => 'Benteng Vredeburg']);
        Destination::factory()->create(['name' => 'Alun-Alun Kidul']);

        $this->get('/katalog?q=Benteng')
            ->assertSee('Benteng Vredeburg')
            ->assertDontSee('Alun-Alun Kidul');
    }

    public function test_falls_back_to_database_when_cache_store_fails(): void
    {
        Cache::shouldReceive('get')->andThrow(new RuntimeException('cache mati'));
        Cache::shouldReceive('put')->andThrow(new RuntimeException('cache mati'));

        $value = CatalogCache::remember('catalog.test', fn () => 'dari-database');

        $this->assertSame('dari-database', $value);
    }
}
