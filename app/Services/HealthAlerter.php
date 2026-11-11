<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\SystemHealthAlertNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Mengirim peringatan saat status kesehatan berubah, dengan jeda
 * (cooldown) supaya tim tidak dibanjiri email untuk masalah yang sama.
 *
 * Status terakhir disimpan di FILE lokal (bukan cache/database), karena
 * justru saat cache atau database mati peringatan harus tetap jalan.
 */
class HealthAlerter
{
    /**
     * @return string|null jenis peringatan yang dikirim: "problem", "recovered", atau null
     */
    public function handle(array $report): ?string
    {
        $state = $this->readState();
        $previous = $state['status'] ?? 'ok';
        $current = $report['status'];
        $cooldown = max(1, (int) config('resilience.alert_cooldown_minutes', 30)) * 60;

        $kind = null;

        if ($current !== 'ok') {
            $changed = $current !== $previous;
            $cooledDown = now()->timestamp - (int) ($state['alerted_at'] ?? 0) >= $cooldown;

            if ($changed || $cooledDown) {
                $kind = 'problem';
            }
        } elseif ($previous !== 'ok') {
            $kind = 'recovered';
        }

        if ($kind !== null) {
            $this->send($report, $kind === 'recovered');
            $state['alerted_at'] = now()->timestamp;
        }

        $state['status'] = $current;
        $this->writeState($state);

        return $kind;
    }

    private function send(array $report, bool $recovered): void
    {
        $notification = new SystemHealthAlertNotification($report, $recovered);
        $level = $recovered ? 'info' : ($report['status'] === 'down' ? 'critical' : 'warning');

        Log::log($level, $notification->subject(), ['checks' => $report['checks']]);

        if ($email = config('resilience.alert_email')) {
            try {
                Notification::route('mail', $email)->notifyNow($notification);
            } catch (Throwable $e) {
                Log::error('Gagal mengirim email peringatan kesehatan', ['error' => $e->getMessage()]);
            }
        }

        // Notifikasi in-app untuk admin; gagal wajar bila database yang mati.
        try {
            User::where('role', 'admin')->get()->each->notifyNow($notification);
        } catch (Throwable $e) {
            Log::warning('Notifikasi admin tidak terkirim (database tidak tersedia?)', ['error' => $e->getMessage()]);
        }
    }

    private function path(): string
    {
        return storage_path('framework/health-alert-state.json');
    }

    private function readState(): array
    {
        $raw = @file_get_contents($this->path());
        $state = $raw ? json_decode($raw, true) : null;

        return is_array($state) ? $state : [];
    }

    private function writeState(array $state): void
    {
        @file_put_contents($this->path(), json_encode($state), LOCK_EX);
    }
}
