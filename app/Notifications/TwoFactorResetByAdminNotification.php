<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TwoFactorResetByAdminNotification extends Notification implements ShouldQueue
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
            ->subject('2FA akunmu telah di-reset oleh Admin')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Admin telah mereset verifikasi dua langkah (2FA) di akun JogjaTrip kamu.')
            ->line('Saat login berikutnya, kamu akan diminta menyiapkan 2FA kembali.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'message' => '2FA akunmu telah di-reset oleh Admin. Siapkan 2FA kembali saat login berikutnya.',
        ];
    }
}
