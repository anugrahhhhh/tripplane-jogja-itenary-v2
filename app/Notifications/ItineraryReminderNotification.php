<?php

namespace App\Notifications;

use App\Models\Itinerary;
use App\Notifications\Concerns\RetriesDelivery;
use App\Support\OutboundUrl;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ItineraryReminderNotification extends Notification implements ShouldQueue
{
    use Queueable;
    use RetriesDelivery;

    /**
     * Lewat antrean: email dan notifikasi in-app dikirim sebagai job
     * terpisah per penerima, jadi SMTP yang gangguan tidak menggagalkan
     * notifikasi in-app atau penerima lain, dan dicoba ulang otomatis
     * (RetriesDelivery). Dengan QUEUE_CONNECTION=sync tetap terkirim langsung.
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
