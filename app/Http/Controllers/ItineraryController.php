<?php

namespace App\Http\Controllers;

use App\Models\Destination;
use App\Models\Itinerary;
use App\Services\DistanceService;
use App\Services\ScheduleValidator;
use App\Services\WeatherService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ItineraryController extends Controller
{
    private const TRAVELER_COUNT_MESSAGES = [
        'traveler_count.required' => 'Jumlah peserta harus antara 1 dan 50.',
        'traveler_count.integer' => 'Jumlah peserta harus antara 1 dan 50.',
        'traveler_count.min' => 'Jumlah peserta harus antara 1 dan 50.',
        'traveler_count.max' => 'Jumlah peserta harus antara 1 dan 50.',
    ];

    /**
     * Titik koordinat kota Yogyakarta dipakai sebagai representasi cuaca
     * untuk seluruh itinerary — cukup akurat karena Kota Yogyakarta hanya
     * ±32 km², cuaca tidak bervariasi signifikan antar destinasi di dalamnya.
     */
    private const CITY_LAT = -7.8014;

    private const CITY_LNG = 110.3644;

    public function index(): View
    {
        $userId = auth()->id();

        $itineraries = Itinerary::query()
            ->where('user_id', $userId)
            ->orWhereHas('collaborators', fn ($query) => $query->where('users.id', $userId))
            ->withCount('items')
            ->with(['items.destination.category', 'user'])
            ->orderByDesc('start_date')
            ->paginate(10);

        return view('itineraries.index', ['itineraries' => $itineraries]);
    }

    public function create(): View
    {
        return view('itineraries.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'traveler_count' => ['required', 'integer', 'min:1', 'max:50'],
        ], self::TRAVELER_COUNT_MESSAGES);

        $itinerary = auth()->user()->itineraries()->create([
            ...$data,
            'type' => 'planned',
        ]);

        return redirect()->route('itineraries.show', $itinerary)
            ->with('status', 'Itinerary berhasil dibuat.');
    }

    public function show(Itinerary $itinerary, WeatherService $weather): View
    {
        $this->authorizeAccess($itinerary);

        $itinerary->load('items.destination.category', 'collaborators', 'user');

        $favoriteDestinations = auth()->user()->favorites()->with('destination')->get()->pluck('destination');
        $catalogDestinations = Destination::where('is_active', true)->orderBy('name')->get();

        [$mapPointsByDay, $warningsByDay, $travelInfoByDay] = $this->buildDayInsights($itinerary);
        $weatherByDay = $this->buildWeatherByDay($itinerary, $weather);

        return view('itineraries.show', [
            'itinerary' => $itinerary,
            'isOwner' => $itinerary->isOwnedBy(auth()->user()),
            'favoriteDestinations' => $favoriteDestinations,
            'catalogDestinations' => $catalogDestinations,
            'mapPointsByDay' => $mapPointsByDay,
            'warningsByDay' => $warningsByDay,
            'travelInfoByDay' => $travelInfoByDay,
            'weatherByDay' => $weatherByDay,
        ]);
    }

    public function edit(Itinerary $itinerary): View
    {
        $this->authorizeOwner($itinerary);

        return view('itineraries.edit', ['itinerary' => $itinerary]);
    }

    public function update(Request $request, Itinerary $itinerary): RedirectResponse
    {
        $this->authorizeOwner($itinerary);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'traveler_count' => ['required', 'integer', 'min:1', 'max:50'],
        ], self::TRAVELER_COUNT_MESSAGES);

        $itinerary->update($data);

        return redirect()->route('itineraries.show', $itinerary)
            ->with('status', 'Itinerary berhasil diperbarui.');
    }

    public function destroy(Itinerary $itinerary): RedirectResponse
    {
        $this->authorizeOwner($itinerary);

        $itinerary->delete();

        return redirect()->route('itineraries.index')
            ->with('status', 'Itinerary berhasil dihapus.');
    }

    public function pdf(Itinerary $itinerary): Response
    {
        $this->authorizeAccess($itinerary);

        $itinerary->load('items.destination');

        [, $warningsByDay, $travelInfoByDay] = $this->buildDayInsights($itinerary);

        $pdf = Pdf::loadView('itineraries.pdf', [
            'itinerary' => $itinerary,
            'warningsByDay' => $warningsByDay,
            'travelInfoByDay' => $travelInfoByDay,
        ]);

        return $pdf->download('itinerary-'.$itinerary->id.'.pdf');
    }

    /**
     * Hanya pemilik: mengubah judul/tanggal, menghapus itinerary, dan
     * mengelola daftar kolaborator.
     */
    protected function authorizeOwner(Itinerary $itinerary): void
    {
        abort_unless($itinerary->isOwnedBy(auth()->user()), 403);
    }

    /**
     * Pemilik maupun kolaborator: melihat, menyusun destinasi, urutkan
     * otomatis, unduh PDF.
     */
    protected function authorizeAccess(Itinerary $itinerary): void
    {
        abort_unless($itinerary->isAccessibleBy(auth()->user()), 403);
    }

    /**
     * Siapkan data titik peta, peringatan jadwal (bentrok/di luar jam
     * operasional), dan estimasi jarak+waktu tempuh antar destinasi
     * berurutan, dikelompokkan per hari.
     *
     * @return array{0: array, 1: array, 2: array}
     */
    protected function buildDayInsights(Itinerary $itinerary): array
    {
        $mapPointsByDay = [];
        $warningsByDay = [];
        $travelInfoByDay = [];

        foreach ($itinerary->itemsByDay() as $day => $items) {
            $mapPointsByDay[$day] = $items->map(fn ($item) => [
                'lat' => $item->destination->latitude,
                'lng' => $item->destination->longitude,
                'name' => $item->destination->name,
            ])->values();

            $warningsByDay[$day] = ScheduleValidator::validateDay($items);
            $travelInfoByDay[$day] = $this->travelInfoBetween($items);
        }

        return [$mapPointsByDay, $warningsByDay, $travelInfoByDay];
    }

    /**
     * Prakiraan cuaca per day_number, diambil sekali untuk seluruh rentang
     * tanggal itinerary (bukan per hari) supaya hanya satu panggilan API.
     */
    protected function buildWeatherByDay(Itinerary $itinerary, WeatherService $weather): array
    {
        $forecast = $weather->forecastForDateRange(
            self::CITY_LAT,
            self::CITY_LNG,
            $itinerary->start_date,
            $itinerary->end_date
        );

        $weatherByDay = [];

        foreach (range(1, $itinerary->totalDays()) as $day) {
            $date = $itinerary->start_date->copy()->addDays($day - 1)->toDateString();
            $weatherByDay[$day] = $forecast[$date] ?? null;
        }

        return $weatherByDay;
    }

    /**
     * Jarak & estimasi waktu tempuh dari destinasi sebelumnya ke destinasi
     * ini, dikunci berdasarkan itinerary_item id "tujuan".
     */
    protected function travelInfoBetween(Collection $items): array
    {
        $info = [];
        $itemsList = $items->values();

        for ($i = 0; $i < $itemsList->count() - 1; $i++) {
            $current = $itemsList[$i];
            $next = $itemsList[$i + 1];

            $distance = DistanceService::haversineKm(
                $current->destination->latitude,
                $current->destination->longitude,
                $next->destination->latitude,
                $next->destination->longitude
            );

            $info[$next->id] = [
                'distance_km' => round($distance, 1),
                'duration_minutes' => DistanceService::estimateDurationMinutes($distance),
            ];
        }

        return $info;
    }
}
