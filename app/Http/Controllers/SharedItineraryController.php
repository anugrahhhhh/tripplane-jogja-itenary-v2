<?php

namespace App\Http\Controllers;

use App\Models\Itinerary;
use App\Services\ItineraryCostEstimator;
use App\Services\ItineraryInsights;
use Illuminate\Http\Response;

class SharedItineraryController extends Controller
{
    /**
     * Halaman publik read-only. Sengaja tidak memuat pemilik, kolaborator,
     * maupun catatan item.
     */
    public function show(string $token): Response
    {
        abort_unless(preg_match('/\A[A-Za-z0-9]{40}\z/', $token) === 1, 404);

        $itinerary = Itinerary::where('share_token', $token)->firstOrFail();
        $itinerary->load('items.destination');

        [$mapPointsByDay, $warningsByDay, $travelInfoByDay] = ItineraryInsights::build($itinerary);

        $response = response(view('itineraries.shared', [
            'itinerary' => $itinerary,
            'mapPointsByDay' => $mapPointsByDay,
            'warningsByDay' => $warningsByDay,
            'travelInfoByDay' => $travelInfoByDay,
            'cost' => (new ItineraryCostEstimator)->estimate($itinerary),
        ]));

        $response->header('X-Robots-Tag', 'noindex, nofollow');

        return $response;
    }
}
