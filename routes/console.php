<?php

use Illuminate\Support\Facades\Schedule;

// Pengingat H-1 itinerary, dijalankan tiap pagi. Perlu cron/Task Scheduler
// yang memanggil `php artisan schedule:run` setiap menit di server produksi
// agar jadwal ini benar-benar aktif (tidak berjalan otomatis di dev lokal).
Schedule::command('app:send-itinerary-reminders')->dailyAt('08:00');

Schedule::command('trusted-devices:prune')->daily();
