<?php

namespace App\Notifications;

use App\Support\OutboundUrl;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Peringatan otomatis saat status kesehatan sistem berubah
 * (normal -> bermasalah, atau bermasalah -> pulih).
 *
 * Sengaja TIDAK lewat antrean: saat sistem bermasalah, antrean bisa jadi
 * ikut mati, padahal peringatan inilah yang paling perlu sampai.
 */
class SystemHealthAlertNotification extends Notification
{
    private const LABELS = [
        'database' => 'Database', 'database_replica' => 'Replika database', 'cache' => 'Cache',
        'storage' => 'Penyimpanan', 'queue' => 'Antrean', 'queue_worker' => 'Worker antrean',
        'scheduler' => 'Scheduler', 'backup' => 'Backup database', 'circuit_breakers' => 'Layanan eksternal',
    ];

    public function __construct(private array $report, private bool $recovered = false) {}

    public function via(object $notifiable): array
    {
        // Alamat email darurat (HEALTH_ALERT_EMAIL) -> email; admin -> in-app.
        return $notifiable instanceof AnonymousNotifiable ? ['mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject())
            ->line($this->summary());

        if ($this->recovered) {
            $mail->success();
        } else {
            $mail->error();

            foreach ($this->problems() as $line) {
                $mail->line('• '.$line);
            }
        }

        return $mail
            ->action('Buka Dashboard Admin', OutboundUrl::using(fn () => route('admin.dashboard')))
            ->line('Diperiksa: '.$this->report['checked_at']);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'system_health',
            'status' => $this->report['status'],
            'message' => $this->summary().($this->recovered ? '' : ' '.implode('; ', $this->problems())),
            'action_url' => route('admin.dashboard'),
            'action_label' => 'Lihat Kesehatan Sistem',
        ];
    }

    public function subject(): string
    {
        return match (true) {
            $this->recovered => '[JogjaTrip] PULIH: sistem kembali normal',
            $this->report['status'] === 'down' => '[JogjaTrip] GANGGUAN: komponen inti tidak berfungsi',
            default => '[JogjaTrip] PERINGATAN: sistem perlu perhatian',
        };
    }

    private function summary(): string
    {
        return match (true) {
            $this->recovered => 'Semua komponen sistem kembali normal.',
            $this->report['status'] === 'down' => 'Sistem mengalami gangguan. Pengguna kemungkinan tidak bisa memakai aplikasi.',
            default => 'Sistem masih melayani pengguna, tetapi ada komponen yang perlu diperiksa.',
        };
    }

    /** @return array<int, string> */
    private function problems(): array
    {
        return collect($this->report['checks'])
            ->reject(fn (array $check) => $check['status'] === 'ok')
            ->map(fn (array $check, string $name) => (self::LABELS[$name] ?? $name).': '.$check['message'])
            ->values()
            ->all();
    }
}
