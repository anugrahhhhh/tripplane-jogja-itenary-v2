<?php

namespace App\Http\Controllers;

use App\Models\Itinerary;
use App\Models\ItineraryItem;
use App\Services\NearestNeighborSorter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ItineraryItemController extends Controller
{
    public function store(Request $request, Itinerary $itinerary): RedirectResponse
    {
        $this->authorizeAccess($itinerary);

        $data = $request->validate([
            'destination_id' => ['required', 'integer', Rule::exists('destinations', 'id')->where('is_active', true)],
            'day_number' => ['required', 'integer', 'min:1', 'max:'.$itinerary->totalDays()],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:600'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $nextOrder = $itinerary->items()->where('day_number', $data['day_number'])->max('sort_order');

        $itinerary->items()->create([
            ...$data,
            'sort_order' => is_null($nextOrder) ? 0 : $nextOrder + 1,
        ]);

        return back()->with('status', 'Destinasi ditambahkan ke itinerary.');
    }

    public function update(Request $request, ItineraryItem $itineraryItem): RedirectResponse
    {
        $this->authorizeAccess($itineraryItem->itinerary);

        $data = $request->validate([
            'day_number' => ['required', 'integer', 'min:1', 'max:'.$itineraryItem->itinerary->totalDays()],
            'start_time' => ['required', 'date_format:H:i'],
            'duration_minutes' => ['required', 'integer', 'min:15', 'max:600'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $itineraryItem->update($data);

        return back()->with('status', 'Jadwal kunjungan diperbarui.');
    }

    public function destroy(ItineraryItem $itineraryItem): RedirectResponse
    {
        $this->authorizeAccess($itineraryItem->itinerary);

        $itineraryItem->delete();

        return back()->with('status', 'Destinasi dihapus dari itinerary.');
    }

    /**
     * Ubah urutan (drag-and-drop) di dalam satu hari yang sama.
     * item_ids dikirim sudah dalam urutan baru.
     */
    public function reorder(Request $request, Itinerary $itinerary): RedirectResponse
    {
        $this->authorizeAccess($itinerary);

        $data = $request->validate([
            'item_ids' => ['required', 'array'],
            'item_ids.*' => ['integer', 'exists:itinerary_items,id'],
        ]);

        // Satu transaksi: kalau gagal di tengah, urutan lama tetap utuh
        // (tidak tersimpan setengah lama, setengah baru).
        DB::transaction(function () use ($data, $itinerary) {
            foreach ($data['item_ids'] as $order => $itemId) {
                ItineraryItem::where('id', $itemId)
                    ->where('itinerary_id', $itinerary->id)
                    ->update(['sort_order' => $order]);
            }
        });

        return back()->with('status', 'Urutan berhasil diperbarui.');
    }

    /**
     * Tombol "Urutkan Otomatis": susun ulang destinasi pada satu hari
     * memakai algoritma nearest neighbor berbasis jarak Haversine, dimulai
     * dari destinasi pertama pada hari itu.
     */
    public function autoSort(Request $request, Itinerary $itinerary): RedirectResponse
    {
        $this->authorizeAccess($itinerary);

        $data = $request->validate([
            'day_number' => ['required', 'integer', 'min:1', 'max:'.$itinerary->totalDays()],
        ]);

        $dayItems = $itinerary->items()
            ->where('day_number', $data['day_number'])
            ->with('destination')
            ->get();

        if ($dayItems->count() < 2) {
            return back()->with('status', 'Minimal butuh 2 destinasi di hari ini untuk diurutkan otomatis.');
        }

        $sorted = NearestNeighborSorter::sort($dayItems);

        DB::transaction(function () use ($sorted) {
            foreach ($sorted->values() as $index => $item) {
                $item->update(['sort_order' => $index]);
            }
        });

        return back()->with('status', 'Urutan berhasil disusun otomatis berdasarkan jarak terdekat.');
    }

    protected function authorizeAccess(Itinerary $itinerary): void
    {
        abort_unless($itinerary->isAccessibleBy(auth()->user()), 403);
    }
}
