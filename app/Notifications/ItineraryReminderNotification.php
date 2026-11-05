<?php

namespace App\Notifications;

use App\Models\Itinerary;
use App\Support\OutboundUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ItineraryReminderNotification extends Notification
{
    use Queueable;

    /**
     * Tidak implement ShouldQueue: dikirim sinkron karena aplikasi ini
     * belum menjalankan queue worker, jadi notifikasi harus langsung
     * terkirim saat command dijalankan, bukan menunggu di antrean.
     */
    public function __construct(protected Itinerary $itinerary) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $totalDays = $this->itinerary->totalDays();

        return (new MailMessage)
            ->subject('Pengingat: "'.$this->itinerary->title.'" dimulai besok!')
            ->greeting('Halo, '.$notifiable->name.'!')
            ->line('Itinerary "'.$this->itinerary->title.'" dijadwalkan mulai besok, '.$this->itinerary->start_date->translatedFormat('d F Y').'.')
            ->line('Perjalanan ini berlangsung selama '.$totalDays.' hari, sampai '.$this->itinerary->end_date->translatedFormat('d F Y').'.')
            ->action('Lihat Itinerary', OutboundUrl::using(fn () => route('itineraries.show', $this->itinerary)))
            ->line('Jangan lupa cek ulang jadwal dan siapkan perlengkapanmu. Selamat berlibur di Yogyakarta!');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'itinerary_id' => $this->itinerary->id,
            'title' => $this->itinerary->title,
            'start_date' => $this->itinerary->start_date->toDateString(),
            'message' => 'Itinerary "'.$this->itinerary->title.'" dimulai besok, '.$this->itinerary->start_date->translatedFormat('d F Y').'.',
        ];
    }
}
