<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class TwoFactorEnabledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

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
            ->subject('2FA telah diaktifkan di akunmu')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Verifikasi dua langkah (2FA) baru saja diaktifkan di akun JogjaTrip kamu.')
            ->line('Kalau ini bukan kamu, segera amankan akunmu.')
            ->action('Bukan saya', $this->notMeUrl($notifiable));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Verifikasi dua langkah (2FA) telah diaktifkan di akunmu.',
            'action_url' => $this->notMeUrl($notifiable),
            'action_label' => 'Bukan saya',
        ];
    }

    private function notMeUrl(object $notifiable): string
    {
        return URL::temporarySignedRoute('security.not-me', now()->addHours(24), ['user' => $notifiable->id]);
    }
}
