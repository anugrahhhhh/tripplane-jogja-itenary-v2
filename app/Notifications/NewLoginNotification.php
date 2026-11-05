<?php

namespace App\Notifications;

use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class NewLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        protected string $ipAddress,
        protected string $device,
        protected string $methodLabel,
        protected CarbonInterface $at,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Login baru ke akun JogjaTrip kamu')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Ada login baru ke akunmu.')
            ->line('Perangkat: '.$this->device)
            ->line('Alamat IP: '.$this->ipAddress)
            ->line('Metode: '.$this->methodLabel)
            ->line('Waktu: '.$this->formattedTime())
            ->line('Kalau ini kamu, abaikan email ini. Kalau bukan, segera amankan akunmu.')
            ->action('Bukan saya', $this->notMeUrl($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Login baru dari '.$this->device.' ('.$this->ipAddress.')',
            'action_url' => $this->notMeUrl($notifiable),
            'action_label' => 'Bukan saya',
            'ip_address' => $this->ipAddress,
            'device' => $this->device,
            'method' => $this->methodLabel,
            'logged_in_at' => $this->at->toIso8601String(),
        ];
    }

    private function formattedTime(): string
    {
        return $this->at->copy()->timezone('Asia/Jakarta')->translatedFormat('d F Y H:i').' WIB';
    }

    private function notMeUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute('security.not-me', now()->addHours(24), ['user' => $notifiable->id]);
    }
}
