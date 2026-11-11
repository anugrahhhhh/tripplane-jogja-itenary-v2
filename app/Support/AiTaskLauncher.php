<?php

namespace App\Support;

use App\Jobs\ProcessAiTask;
use App\Models\AiTask;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Membuat AiTask, mengirimnya ke queue, lalu mengarahkan pengguna:
 * - ke halaman progres bila diproses worker (normal di produksi),
 * - langsung ke hasil/error bila queue sinkron (dev/test) sudah selesai.
 */
class AiTaskLauncher
{
    private const LIMITS = [
        AiTask::TYPE_ITINERARY => ['max' => 3, 'decay' => 120],
        AiTask::TYPE_RECOMMENDATION => ['max' => 5, 'decay' => 60],
    ];

    public function __construct(private Dispatcher $bus) {}

    /** Pesan error bila kena batas percobaan, atau null kalau masih boleh. */
    public function throttle(Request $request, string $type): ?string
    {
        $key = "ai-{$type}:".$request->ip();
        $limit = self::LIMITS[$type];

        if (RateLimiter::tooManyAttempts($key, $limit['max'])) {
            $seconds = RateLimiter::availableIn($key);

            return "Terlalu banyak percobaan. Coba lagi dalam {$seconds} detik.";
        }

        RateLimiter::hit($key, $limit['decay']);

        return null;
    }

    public function launch(Request $request, string $type, array $input): RedirectResponse
    {
        $user = $request->user();

        $task = AiTask::create([
            'user_id' => $user?->id,
            'guest_token' => $user ? null : AiTask::guestTokenFor($request),
            'type' => $type,
            'status' => AiTask::STATUS_QUEUED,
            'input' => $input,
        ]);

        return $this->run($task);
    }

    /** Jalankan ulang task gagal dengan input yang sama. */
    public function retry(AiTask $task): RedirectResponse
    {
        $task->forceFill([
            'status' => AiTask::STATUS_QUEUED,
            'error' => null,
            'result' => null,
            'started_at' => null,
            'finished_at' => null,
            'created_at' => now(),
        ])->save();

        return $this->run($task);
    }

    private function run(AiTask $task): RedirectResponse
    {
        try {
            $this->bus->dispatch(new ProcessAiTask($task));
        } catch (Throwable $e) {
            // Queue tidak bisa dipakai (mis. Redis/antrean bermasalah):
            // tetap layani pengguna dengan memproses langsung.
            report($e);
            $this->bus->dispatchSync(new ProcessAiTask($task));
        }

        $task->refresh();

        if ($task->status === AiTask::STATUS_COMPLETED) {
            return redirect()->to($task->resultUrl())->with('status', self::successMessage($task));
        }

        if ($task->status === AiTask::STATUS_FAILED) {
            return redirect()->to($task->formRoute())->withInput($task->input)->with('error', $task->error);
        }

        return redirect()->route('ai-tasks.show', $task);
    }

    public static function successMessage(AiTask $task): ?string
    {
        return $task->type === AiTask::TYPE_ITINERARY
            ? 'Itinerary berhasil disusun otomatis oleh AI. Silakan cek dan sesuaikan kalau perlu.'
            : null;
    }
}
