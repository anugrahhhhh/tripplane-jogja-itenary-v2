<?php

namespace App\Http\Controllers;

use App\Models\AiTask;
use App\Models\Destination;
use App\Support\AiTaskLauncher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman progres, status (dipolling), dan hasil permintaan AI yang
 * diproses di latar belakang.
 */
class AiTaskController extends Controller
{
    public function show(Request $request, AiTask $aiTask): View|RedirectResponse
    {
        $this->authorizeOwner($request, $aiTask);
        $aiTask->markFailedIfStale();

        // Tanpa JavaScript: halaman ini di-refresh otomatis lalu diarahkan
        // ke hasil begitu selesai.
        if ($aiTask->status === AiTask::STATUS_COMPLETED) {
            return redirect()->to($aiTask->resultUrl())->with('status', AiTaskLauncher::successMessage($aiTask));
        }

        return view('ai-tasks.show', ['task' => $aiTask]);
    }

    public function status(Request $request, AiTask $aiTask): JsonResponse
    {
        $this->authorizeOwner($request, $aiTask);
        $aiTask->markFailedIfStale();

        if ($aiTask->status === AiTask::STATUS_COMPLETED && ($message = AiTaskLauncher::successMessage($aiTask))) {
            $request->session()->flash('status', $message);
        }

        return response()->json([
            'status' => $aiTask->status,
            'redirect' => $aiTask->resultUrl(),
            'error' => $aiTask->status === AiTask::STATUS_FAILED ? $aiTask->error : null,
            'elapsed' => (int) $aiTask->created_at->diffInSeconds(now()),
        ])->header('Cache-Control', 'no-store');
    }

    public function result(Request $request, AiTask $aiTask): View|RedirectResponse
    {
        $this->authorizeOwner($request, $aiTask);
        abort_unless($aiTask->type === AiTask::TYPE_RECOMMENDATION, 404);

        if ($aiTask->status !== AiTask::STATUS_COMPLETED) {
            return redirect()->route('ai-tasks.show', $aiTask);
        }

        $rows = collect($aiTask->result ?? []);
        $destinations = Destination::with('category')
            ->where('is_active', true)
            ->whereIn('id', $rows->pluck('destination_id'))
            ->get()
            ->keyBy('id');

        $recommendations = $rows
            ->filter(fn (array $row) => $destinations->has($row['destination_id']))
            ->map(fn (array $row) => [
                'destination' => $destinations[$row['destination_id']],
                'reason' => (string) $row['reason'],
            ])
            ->values()
            ->all();

        return view('recommendations.result', [
            'recommendations' => $recommendations,
            'preferences' => $aiTask->input,
            'lat' => $aiTask->input['lat'] ?? null,
            'lng' => $aiTask->input['lng'] ?? null,
        ]);
    }

    public function retry(Request $request, AiTask $aiTask, AiTaskLauncher $launcher): RedirectResponse
    {
        $this->authorizeOwner($request, $aiTask);
        $aiTask->markFailedIfStale();

        if ($aiTask->status !== AiTask::STATUS_FAILED) {
            return redirect()->route('ai-tasks.show', $aiTask);
        }

        if ($error = $launcher->throttle($request, $aiTask->type)) {
            return redirect()->route('ai-tasks.show', $aiTask)->with('error', $error);
        }

        return $launcher->retry($aiTask);
    }

    /** Kembali ke form dengan isian sebelumnya untuk diubah. */
    public function edit(Request $request, AiTask $aiTask): RedirectResponse
    {
        $this->authorizeOwner($request, $aiTask);

        return redirect()->to($aiTask->formRoute())->withInput($aiTask->input);
    }

    private function authorizeOwner(Request $request, AiTask $aiTask): void
    {
        // 404 (bukan 403) supaya keberadaan task milik orang lain tidak bocor.
        abort_unless($aiTask->isOwnedBy($request), 404);
    }
}
