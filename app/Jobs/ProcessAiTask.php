<?php

namespace App\Jobs;

use App\Models\AiTask;
use App\Services\AiItineraryPlannerService;
use App\Services\LlmRecommendationService;
use App\Support\Transaction;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Menjalankan permintaan AI di worker queue. Request web hanya membuat
 * AiTask lalu langsung kembali, jadi pengguna melihat halaman progres
 * alih-alih layar yang "membeku" sampai 50 detik.
 */
class ProcessAiTask implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /** AI tidak dicoba ulang otomatis: pengguna bisa menekan "Coba lagi". */
    public int $tries = 1;

    /** Di atas batas waktu Gemini (maks 150 dtk) dan di bawah retry_after queue. */
    public int $timeout = 170;

    public function __construct(public AiTask $task)
    {
        $this->onQueue('ai');
    }

    public function handle(AiItineraryPlannerService $planner, LlmRecommendationService $recommender): void
    {
        $task = $this->task->fresh();

        // Sudah selesai (mis. dianggap macet lalu dicoba ulang dari task baru).
        if (! $task || $task->isFinished()) {
            return;
        }

        $task->forceFill([
            'status' => AiTask::STATUS_PROCESSING,
            'started_at' => now(),
            'attempts' => $task->attempts + 1,
        ])->save();

        try {
            $task->type === AiTask::TYPE_ITINERARY
                ? $this->runItinerary($task, $planner)
                : $this->runRecommendation($task, $recommender);
        } catch (RuntimeException $e) {
            // Pesan RuntimeException dari service sudah ramah pengguna.
            $this->markFailed($task, $e->getMessage());
        }
    }

    /** Dipanggil Laravel bila job crash/timeout di luar kendali handle(). */
    public function failed(?Throwable $e): void
    {
        Log::error('Task AI gagal diproses', ['task' => $this->task->id, 'error' => $e?->getMessage()]);

        $task = $this->task->fresh();

        if ($task && ! $task->isFinished()) {
            $this->markFailed($task, 'Terjadi kendala saat memproses permintaan AI. Silakan coba lagi.');
        }
    }

    private function runItinerary(AiTask $task, AiItineraryPlannerService $planner): void
    {
        $input = $task->input;
        $startDate = Carbon::parse($input['start_date']);
        $endDate = Carbon::parse($input['end_date']);

        $days = $planner->plan([
            'interests' => $input['interests'] ?? [],
            'notes' => $input['notes'] ?? null,
            'budget_per_day' => $input['budget_per_day'] ?? null,
            'total_days' => (int) $startDate->diffInDays($endDate) + 1,
            'lat' => $input['lat'] ?? null,
            'lng' => $input['lng'] ?? null,
        ]);

        if (empty($days)) {
            $this->markFailed($task, 'AI tidak berhasil menyusun itinerary untuk preferensi ini. Coba ubah preferensimu.');

            return;
        }

        Transaction::run(function () use ($task, $input, $startDate, $endDate, $days) {
            $itinerary = $task->user->itineraries()->create([
                'title' => ($input['title'] ?? null) ?: 'Itinerary AI - '.$startDate->translatedFormat('d M Y'),
                'start_date' => $startDate,
                'end_date' => $endDate,
                'type' => 'planned',
            ]);

            foreach ($days as $dayNumber => $items) {
                foreach (array_values($items) as $sortOrder => $item) {
                    $itinerary->items()->create([
                        'destination_id' => $item['destination']->id,
                        'day_number' => $dayNumber,
                        'start_time' => $item['start_time'],
                        'duration_minutes' => $item['duration_minutes'],
                        'sort_order' => $sortOrder,
                        'notes' => $item['note'],
                    ]);
                }
            }

            $task->forceFill([
                'status' => AiTask::STATUS_COMPLETED,
                'itinerary_id' => $itinerary->id,
                'finished_at' => now(),
            ])->save();
        });
    }

    private function runRecommendation(AiTask $task, LlmRecommendationService $recommender): void
    {
        $input = $task->input;

        $recommendations = $recommender->recommend([
            'interests' => $input['interests'] ?? [],
            'notes' => $input['notes'] ?? null,
            'budget_max' => $input['budget_max'] ?? null,
            'duration' => $input['duration_label'] ?? null,
            'lat' => $input['lat'] ?? null,
            'lng' => $input['lng'] ?? null,
        ]);

        // Simpan ID saja; model dimuat ulang saat halaman hasil dibuka.
        $task->forceFill([
            'status' => AiTask::STATUS_COMPLETED,
            'result' => array_map(fn (array $item) => [
                'destination_id' => $item['destination']->id,
                'reason' => $item['reason'],
            ], $recommendations),
            'finished_at' => now(),
        ])->save();
    }

    private function markFailed(AiTask $task, string $message): void
    {
        $task->forceFill([
            'status' => AiTask::STATUS_FAILED,
            'error' => mb_substr($message, 0, 500),
            'finished_at' => now(),
        ])->save();
    }
}
