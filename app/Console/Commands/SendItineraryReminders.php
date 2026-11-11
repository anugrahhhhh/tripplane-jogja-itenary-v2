<?php

namespace App\Console\Commands;

use App\Models\Itinerary;
use App\Notifications\ItineraryReminderNotification;
use Illuminate\Console\Command;
use Throwable;

class SendItineraryReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-itinerary-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Kirim pengingat (email + notifikasi dalam-aplikasi) H-1 untuk itinerary yang mulai besok, ke pemilik dan seluruh kolaborator.';

    public function handle(): int
    {
        $tomorrow = today()->addDay();

        $itineraries = Itinerary::query()
            ->whereDate('start_date', $tomorrow)
            ->whereNull('reminder_sent_at')
            ->with(['user', 'collaborators'])
            ->get();

        if ($itineraries->isEmpty()) {
            $this->info('Tidak ada itinerary yang mulai besok ('.$tomorrow->toDateString().'). Tidak ada pengingat dikirim.');

            return self::SUCCESS;
        }

        $failed = 0;

        foreach ($itineraries as $itinerary) {
            // Isolasi per itinerary: satu kegagalan (mis. data rusak, antrean
            // gangguan) tidak menghentikan pengingat untuk itinerary lain.
            // Itinerary yang gagal tidak ditandai terkirim, sehingga dicoba
            // lagi pada jadwal berikutnya (tiap jam, lihat routes/console.php).
            try {
                $recipients = collect([$itinerary->user])
                    ->merge($itinerary->collaborators)
                    ->filter()
                    ->unique('id');

                foreach ($recipients as $recipient) {
                    $recipient->notify(new ItineraryReminderNotification($itinerary));
                }

                $itinerary->forceFill(['reminder_sent_at' => now()])->save();

                $this->info('Pengingat terkirim: "'.$itinerary->title.'" ke '.$recipients->count().' orang.');
            } catch (Throwable $e) {
                $failed++;
                report($e);
                $this->error('Gagal mengirim pengingat "'.$itinerary->title.'": '.$e->getMessage());
            }
        }

        if ($failed > 0) {
            $this->warn("{$failed} itinerary gagal diproses dan akan dicoba lagi pada jadwal berikutnya.");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
