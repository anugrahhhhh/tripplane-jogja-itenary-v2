<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/**
 * Satu permintaan AI yang diproses di latar belakang (queue).
 *
 * Alur status: queued -> processing -> completed | failed.
 * Permintaan yang tertahan terlalu lama (worker mati/antrean macet) dianggap
 * gagal oleh {@see markFailedIfStale()} supaya pengguna tidak menunggu selamanya.
 */
class AiTask extends Model
{
    use HasUuids;
    use MassPrunable;

    public const TYPE_ITINERARY = 'itinerary';

    public const TYPE_RECOMMENDATION = 'recommendation';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /** Batas tunggu di antrean sebelum dianggap macet (detik). */
    public const MAX_QUEUE_WAIT = 120;

    protected $fillable = [
        'user_id', 'guest_token', 'type', 'status', 'input', 'result',
        'itinerary_id', 'error', 'attempts', 'started_at', 'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'input' => 'array',
            'result' => 'array',
            'attempts' => 'integer',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    /** Hasil permintaan AI hanya disimpan sebentar. */
    public function prunable(): Builder
    {
        return static::where('created_at', '<', now()->subDays(2));
    }

    public static function guestTokenFor(Request $request): string
    {
        return hash('sha256', $request->session()->getId());
    }

    public function isOwnedBy(Request $request): bool
    {
        if ($this->user_id !== null) {
            return $request->user()?->id === $this->user_id;
        }

        return $this->guest_token !== null
            && hash_equals($this->guest_token, self::guestTokenFor($request));
    }

    public function isFinished(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }

    /**
     * Batas maksimal sebuah task boleh berstatus "processing": batas waktu AI
     * ditambah kelonggaran untuk baca katalog & simpan hasil.
     */
    public static function maxProcessingSeconds(): int
    {
        return (int) max(10, min(150, (int) config('services.gemini.time_budget', 50))) + 60;
    }

    /**
     * Tandai gagal bila task macet: terlalu lama di antrean (worker tidak
     * jalan) atau terlalu lama diproses (worker mati di tengah jalan).
     */
    public function markFailedIfStale(): bool
    {
        if ($this->isFinished()) {
            return false;
        }

        $stale = $this->status === self::STATUS_QUEUED
            ? $this->created_at->lt(now()->subSeconds(self::MAX_QUEUE_WAIT))
            : ($this->started_at ?? $this->created_at)->lt(now()->subSeconds(self::maxProcessingSeconds()));

        if (! $stale) {
            return false;
        }

        $this->forceFill([
            'status' => self::STATUS_FAILED,
            'error' => 'Server sedang sibuk sehingga permintaanmu belum sempat diproses. Silakan coba lagi.',
            'finished_at' => now(),
        ])->save();

        return true;
    }

    public function formRoute(): string
    {
        return $this->type === self::TYPE_ITINERARY
            ? route('itineraries.ai-create')
            : route('recommendations.create');
    }

    /** URL tujuan setelah selesai. */
    public function resultUrl(): ?string
    {
        if ($this->status !== self::STATUS_COMPLETED) {
            return null;
        }

        if ($this->type === self::TYPE_ITINERARY) {
            return $this->itinerary_id ? route('itineraries.show', $this->itinerary_id) : null;
        }

        return route('ai-tasks.result', $this);
    }
}
