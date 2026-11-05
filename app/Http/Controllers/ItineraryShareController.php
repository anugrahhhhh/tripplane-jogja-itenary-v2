<?php

namespace App\Http\Controllers;

use App\Models\Itinerary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;

class ItineraryShareController extends Controller
{
    /**
     * Buat link berbagi baru. Dipakai juga untuk "Buat link baru":
     * token lama otomatis tidak berlaku lagi.
     */
    public function store(Itinerary $itinerary): RedirectResponse
    {
        abort_unless($itinerary->isOwnedBy(auth()->user()), 403);

        $itinerary->forceFill(['share_token' => Str::random(40)])->save();

        return redirect()->route('itineraries.show', $itinerary)
            ->with('status', 'Link berbagi dibuat.');
    }

    public function destroy(Itinerary $itinerary): RedirectResponse
    {
        abort_unless($itinerary->isOwnedBy(auth()->user()), 403);

        $itinerary->forceFill(['share_token' => null])->save();

        return redirect()->route('itineraries.show', $itinerary)
            ->with('status', 'Link berbagi dimatikan.');
    }
}
