<?php

namespace App\Notifications\Concerns;

/**
 * Notifikasi antrean dicoba ulang dengan jeda bertahap (exponential
 * backoff): gangguan SMTP sesaat tidak membuat email hilang, dan server
 * email yang sedang down tidak dibanjiri percobaan ulang beruntun.
 */
trait RetriesDelivery
{
    public int $tries = 5;

    /** @return array<int, int> detik sebelum percobaan ke-2, 3, 4, 5 */
    public function backoff(): array
    {
        return [30, 120, 600, 1800];
    }
}
