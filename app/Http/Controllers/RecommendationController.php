<?php

namespace App\Http\Controllers;

use App\Models\AiTask;
use App\Models\Category;
use App\Support\AiTaskLauncher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RecommendationController extends Controller
{
    public function create(): View
    {
        return view('recommendations.create', [
            'categories' => Category::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AiTaskLauncher $launcher): RedirectResponse
    {
        if ($error = $launcher->throttle($request, AiTask::TYPE_RECOMMENDATION)) {
            return back()->withInput()->with('error', $error);
        }

        $data = $request->validate([
            'interests' => ['nullable', 'array'],
            'interests.*' => ['string'],
            'notes' => ['nullable', 'string', 'max:500'],
            'budget_max' => ['nullable', 'integer', 'min:0'],
            'duration' => ['nullable', 'string', 'in:singkat,setengah-hari,seharian'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
        ]);

        $durationLabels = [
            'singkat' => '1-2 jam',
            'setengah-hari' => 'Setengah hari (3-5 jam)',
            'seharian' => 'Seharian (6+ jam)',
        ];

        $data['duration_label'] = $durationLabels[$data['duration'] ?? ''] ?? null;

        return $launcher->launch($request, AiTask::TYPE_RECOMMENDATION, $data);
    }
}
