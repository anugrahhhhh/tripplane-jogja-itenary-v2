<?php

namespace App\Notifications;

use App\Notifications\Concerns\RetriesDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RecoveryCodeUsedNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RetriesDelivery;

    public function __construct(protected int $remaining) {}

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
            ->subject('Recovery code dipakai untuk masuk')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Salah satu recovery code dipakai untuk masuk ke akun JogjaTrip kamu.')
            ->line('Sisa recovery code: '.$this->remaining.' kode.')
            ->line('Pertimbangkan membuat recovery code baru dari pengaturan keamanan akunmu.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Recovery code dipakai untuk masuk. Sisa '.$this->remaining.' kode.',
            'remaining' => $this->remaining,
        ];
    }
}
