<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Circuit breaker sederhana berbasis cache (dibagi semua instance app).
 *
 *   closed    -> normal; kegagalan dihitung
 *   open      -> gagal >= threshold; semua panggilan langsung ditolak
 *                sampai cooldown habis (fail fast, tidak menunggu timeout)
 *   half-open -> setelah cooldown, SATU panggilan percobaan diizinkan;
 *                sukses = closed, gagal = open lagi
 *
 * Bila cache sendiri bermasalah, breaker bersikap "closed" (selalu
 * mengizinkan) supaya gangguan cache tidak ikut mematikan fitur.
 */
class CircuitBreaker
{
    public const CLOSED = 'closed';

    public const OPEN = 'open';

    public const HALF_OPEN = 'half-open';

    public function __construct(
        private string $service,
        private int $threshold,
        private int $cooldown,
    ) {}

    public static function for(string $service): self
    {
        $config = config("resilience.breakers.{$service}", []);

        return new self(
            $service,
            max(1, (int) ($config['threshold'] ?? 3)),
            max(5, (int) ($config['cooldown'] ?? 60)),
        );
    }

    /** Boleh memanggil layanan sekarang? */
    public function allows(): bool
    {
        return $this->safely(function () {
            $openedAt = Cache::get($this->key('opened_at'));

            if ($openedAt === null) {
                return true;
            }

            if (now()->timestamp - (int) $openedAt < $this->cooldown) {
                return false;
            }

            // Cooldown habis: izinkan satu percobaan (half-open). Lock
            // mencegah banyak request sekaligus "menyerbu" layanan yang
            // baru pulih.
            return Cache::add($this->key('probe'), 1, $this->cooldown);
        }, true);
    }

    public function recordSuccess(): void
    {
        $this->safely(function () {
            $wasOpen = Cache::has($this->key('opened_at'));

            Cache::forget($this->key('failures'));
            Cache::forget($this->key('opened_at'));
            Cache::forget($this->key('probe'));

            if ($wasOpen) {
                Log::info("Circuit breaker {$this->service} menutup kembali: layanan pulih.");
            }
        });
    }

    public function recordFailure(): void
    {
        $this->safely(function () {
            Cache::add($this->key('failures'), 0, now()->addHour());
            $failures = (int) Cache::increment($this->key('failures'));
            $probing = Cache::has($this->key('probe'));

            if ($failures >= $this->threshold || $probing) {
                Cache::put($this->key('opened_at'), now()->timestamp, now()->addHour());
                Cache::forget($this->key('probe'));

                Log::warning("Circuit breaker {$this->service} TERBUKA", [
                    'kegagalan_beruntun' => $failures,
                    'cooldown_detik' => $this->cooldown,
                ]);
            }
        });
    }

    public function state(): string
    {
        return $this->safely(function () {
            $openedAt = Cache::get($this->key('opened_at'));

            if ($openedAt === null) {
                return self::CLOSED;
            }

            return now()->timestamp - (int) $openedAt < $this->cooldown ? self::OPEN : self::HALF_OPEN;
        }, self::CLOSED);
    }

    /** Sisa detik sampai percobaan berikutnya diizinkan. */
    public function retryAfter(): int
    {
        return $this->safely(function () {
            $openedAt = Cache::get($this->key('opened_at'));

            return $openedAt === null ? 0 : max(0, $this->cooldown - (now()->timestamp - (int) $openedAt));
        }, 0);
    }

    public function reset(): void
    {
        $this->recordSuccess();
    }

    private function key(string $suffix): string
    {
        return "breaker:{$this->service}:{$suffix}";
    }

    private function safely(callable $callback, mixed $fallback = null): mixed
    {
        try {
            return $callback();
        } catch (Throwable $e) {
            report($e);

            return $fallback;
        }
    }
}
