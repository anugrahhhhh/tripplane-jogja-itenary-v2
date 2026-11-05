<?php

namespace Tests\Feature\Itinerary;

use App\Models\Destination;
use App\Models\Itinerary;
use App\Models\ItineraryItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

/**
 * Penyimpanan berlangkah banyak harus utuh: semua tersimpan, atau tidak sama
 * sekali bila terjadi error di tengah jalan.
 */
class DataIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function itineraryWithItems(int $count): array
    {
        $itinerary = Itinerary::factory()->create();
        $items = collect(range(0, $count - 1))->map(fn ($i) => $itinerary->items()->create([
            'destination_id' => Destination::factory()->create([
                'latitude' => -7.80 + $i * 0.01,
                'longitude' => 110.36 + $i * 0.01,
            ])->id,
            'day_number' => 1,
            'start_time' => '09:00',
            'duration_minutes' => 60,
            'sort_order' => $i,
        ]));

        return [$itinerary, $items];
    }

    /**
     * sort_order per ID item. Dibaca langsung dari tabel (bukan lewat relasi
     * items(), yang sudah punya urutan bawaan berdasarkan sort_order).
     *
     * @return array<int, int>
     */
    private function sortOrders(Itinerary $itinerary): array
    {
        return ItineraryItem::where('itinerary_id', $itinerary->id)
            ->orderBy('id')
            ->pluck('sort_order', 'id')
            ->map(fn ($order) => (int) $order)
            ->all();
    }

    /**
     * Paksa query UPDATE ke-$n pada itinerary_items gagal.
     */
    private function failOnNthItemUpdate(int $n): void
    {
        $seen = 0;
        DB::listen(function ($query) use (&$seen, $n) {
            if (str_starts_with(strtolower($query->sql), 'update "itinerary_items"') && ++$seen === $n) {
                throw new RuntimeException('database putus di tengah jalan');
            }
        });
    }

    public function test_reorder_rolls_back_completely_on_failure(): void
    {
        [$itinerary, $items] = $this->itineraryWithItems(3);
        $before = $this->sortOrders($itinerary);

        $this->failOnNthItemUpdate(2);
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($itinerary->user)->post(route('itinerary-items.reorder', $itinerary), [
                'item_ids' => $items->pluck('id')->reverse()->values()->all(),
            ]);
            $this->fail('Seharusnya gagal.');
        } catch (RuntimeException) {
            // diharapkan
        }

        $this->assertSame($before, $this->sortOrders($itinerary));
    }

    public function test_reorder_succeeds_normally(): void
    {
        [$itinerary, $items] = $this->itineraryWithItems(3);
        $reversed = $items->pluck('id')->reverse()->values()->all();

        $this->actingAs($itinerary->user)
            ->post(route('itinerary-items.reorder', $itinerary), ['item_ids' => $reversed])
            ->assertRedirect();

        $orders = $this->sortOrders($itinerary);
        asort($orders);
        $this->assertSame($reversed, array_keys($orders));
    }

    public function test_auto_sort_rolls_back_completely_on_failure(): void
    {
        // Destinasi berjajar A(0) - B(1) - C(2). Urutan awal A, C, B; urut
        // otomatis dari A menghasilkan A, B, C, jadi B dan C sama-sama berubah
        // (dua UPDATE). UPDATE kedua dibuat gagal.
        [$itinerary, $items] = $this->itineraryWithItems(3);
        $items[1]->update(['sort_order' => 2]);
        $items[2]->update(['sort_order' => 1]);
        $before = $this->sortOrders($itinerary);
        $this->assertSame([0, 2, 1], array_values($before), 'Urutan awal harus A, C, B');

        $this->failOnNthItemUpdate(2);
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($itinerary->user)->post(route('itinerary-items.auto-sort', $itinerary), ['day_number' => 1]);
            $this->fail('Seharusnya gagal di UPDATE kedua.');
        } catch (RuntimeException) {
            // diharapkan
        }

        $this->assertSame($before, $this->sortOrders($itinerary));
    }

    public function test_today_itinerary_rolls_back_completely_on_failure(): void
    {
        $user = User::factory()->create();
        $destinations = Destination::factory()->count(3)->create();

        DB::listen(function ($query) {
            static $inserts = 0;
            if (str_starts_with(strtolower($query->sql), 'insert into "itinerary_items"') && ++$inserts === 2) {
                throw new RuntimeException('database putus di tengah jalan');
            }
        });
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($user)->post(route('today-itinerary.store'), [
                'destination_ids' => $destinations->pluck('id')->all(),
            ]);
            $this->fail('Seharusnya gagal di INSERT kedua.');
        } catch (RuntimeException) {
            // diharapkan
        }

        $this->assertSame(0, $user->itineraries()->count(), 'Itinerary setengah jadi tidak boleh tersimpan');
        $this->assertDatabaseCount('itinerary_items', 0);
    }

    public function test_today_itinerary_skips_inactive_and_duplicate_destinations(): void
    {
        $user = User::factory()->create();
        $active = Destination::factory()->create();
        $inactive = Destination::factory()->create(['is_active' => false]);

        $this->actingAs($user)->post(route('today-itinerary.store'), [
            'destination_ids' => [$active->id, $inactive->id],
        ])->assertRedirect();

        $this->actingAs($user)->post(route('today-itinerary.store'), [
            'destination_ids' => [$active->id],
        ])->assertRedirect();

        $itinerary = $user->itineraries()->where('type', 'today')->sole();
        $this->assertSame([$active->id], $itinerary->items()->pluck('destination_id')->map(fn ($id) => (int) $id)->all());
    }

    public function test_today_itinerary_with_only_inactive_destinations_shows_error(): void
    {
        $user = User::factory()->create();
        $inactive = Destination::factory()->create(['is_active' => false]);

        $this->actingAs($user)
            ->from(route('nearby.index'))
            ->post(route('today-itinerary.store'), ['destination_ids' => [$inactive->id]])
            ->assertRedirect(route('nearby.index'))
            ->assertSessionHasErrors('destination_ids');

        $this->assertSame(0, $user->itineraries()->count());

        $this->get(route('nearby.index'))->assertSee('Destinasi yang dipilih sudah tidak tersedia.');
    }

    public function test_today_itinerary_checks_duplicates_with_one_query(): void
    {
        $user = User::factory()->create();
        $destinations = Destination::factory()->count(8)->create();

        DB::enableQueryLog();
        $this->actingAs($user)->post(route('today-itinerary.store'), [
            'destination_ids' => $destinations->pluck('id')->all(),
        ])->assertRedirect();
        $existsQueries = collect(DB::getQueryLog())
            ->filter(fn ($q) => str_contains(strtolower($q['query']), 'select exists'))
            ->count();
        DB::disableQueryLog();

        $this->assertSame(0, $existsQueries);
        $this->assertSame(8, $user->itineraries()->sole()->items()->count());
    }
}
