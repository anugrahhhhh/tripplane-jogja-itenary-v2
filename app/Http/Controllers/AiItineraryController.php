<?php

namespace App\Http\Controllers;

use App\Models\AiTask;
use App\Models\Category;
use App\Support\AiTaskLauncher;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AiItineraryController extends Controller
{
    public function create(): View
    {
        return view('itineraries.ai-create', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AiTaskLauncher $launcher): RedirectResponse
    {
        if ($error = $launcher->throttle($request, AiTask::TYPE_ITINERARY)) {
            return back()->withInput()->with('error', $error);
        }

        $data = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'interests' => ['nullable', 'array'],
            'interests.*' => ['string'],
            'notes' => ['nullable', 'string', 'max:500'],
            'budget_per_day' => ['nullable', 'integer', 'min:0'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $totalDays = (int) Carbon::parse($data['start_date'])->diffInDays(Carbon::parse($data['end_date'])) + 1;

        if ($totalDays > 7) {
            return back()->withInput()->with('error', 'Maksimal 7 hari untuk satu itinerary buatan AI.');
        }

        // Proses AI (bisa sampai puluhan detik) berjalan di queue; pengguna
        // diarahkan ke halaman progres yang memantau statusnya.
        return $launcher->launch($request, AiTask::TYPE_ITINERARY, $data);
    }
}
