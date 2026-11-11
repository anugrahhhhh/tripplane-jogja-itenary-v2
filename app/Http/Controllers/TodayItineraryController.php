<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Services\DistanceService;
use App\Support\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TodayItineraryController extends Controller
{
    /**
     * "Tambahkan ke Itinerary Hari Ini" dari halaman Rekomendasi Terdekat.
     * Membuat (atau memakai) itinerary bertipe "today" untuk tanggal hari
     * ini, lalu menambahkan destinasi yang dipilih — diurutkan berdasarkan
     * jarak dari lokasi pengguna saat ini (jika lokasinya dikirim).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'destination_ids' => ['required', 'array', 'min:1'],
            'destination_ids.*' => ['integer', 'exists:destinations,id'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $today = today();

        // Destinasi yang sudah dinonaktifkan admin tidak boleh ikut ditambahkan.
        $destinations = Destination::whereIn('id', $data['destination_ids'])
            ->where('is_active', true)
            ->get();

        if ($destinations->isEmpty()) {
            return back()->withErrors(['destination_ids' => 'Destinasi yang dipilih sudah tidak tersedia.']);
        }

        if (! empty($data['lat']) && ! empty($data['lng'])) {
            $lat = (float) $data['lat'];
            $lng = (float) $data['lng'];

            $destinations = $destinations
                ->map(function (Destination $destination) use ($lat, $lng) {
                    $destination->distance_from_user = DistanceService::haversineKm(
                        $lat, $lng, $destination->latitude, $destination->longitude
                    );

                    return $destination;
                })
                ->sortBy('distance_from_user')
                ->values();
        }

        // Satu transaksi: itinerary dan semua destinasinya tersimpan utuh,
        // atau tidak sama sekali kalau ada error di tengah jalan.
        $itinerary = Transaction::run(function () use ($destinations, $today) {
            $itinerary = auth()->user()->itineraries()->firstOrCreate(
                ['type' => 'today', 'start_date' => $today, 'end_date' => $today],
                ['title' => 'Itinerary Hari Ini - '.$today->translatedFormat('d F Y')]
            );

            // Satu query untuk semua destinasi yang sudah ada (bukan satu per destinasi).
            $existingIds = $itinerary->items()->pluck('destination_id')->map(fn ($id) => (int) $id)->flip();

            $startOrder = $itinerary->items()->max('sort_order');
            $startOrder = is_null($startOrder) ? 0 : $startOrder + 1;

            $newIndex = 0;
            $cursor = now();

            foreach ($destinations as $destination) {
                if ($existingIds->has((int) $destination->id)) {
                    continue;
                }

                $itinerary->items()->create([
                    'destination_id' => $destination->id,
                    'day_number' => 1,
                    'start_time' => $cursor->format('H:i'),
                    'duration_minutes' => 60,
                    'sort_order' => $startOrder + $newIndex,
                ]);

                $existingIds->put((int) $destination->id, true);
                $cursor = $cursor->copy()->addMinutes(90);
                $newIndex++;
            }

            return $itinerary;
        });

        return redirect()->route('itineraries.show', $itinerary)
            ->with('status', 'Itinerary Hari Ini berhasil dibuat, destinasi diurutkan berdasarkan jarak dari lokasimu.');
    }
}
