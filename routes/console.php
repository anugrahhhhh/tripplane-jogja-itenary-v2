<?php

use App\Models\AiTask;
use App\Services\SystemHealth;
use Illuminate\Support\Facades\Schedule;

// onOneServer: bila ada beberapa instance yang menjalankan scheduler (mis.
// replika PaaS), tiap tugas tetap hanya dijalankan satu instance.
//
// Semua jadwal butuh `php artisan schedule:work` (Docker/PaaS sudah
// menjalankannya) atau cron `php artisan schedule:run` setiap menit.

// Pengingat H-1 itinerary: pertama kali pukul 08:00, lalu tiap jam sampai
// 20:00 sebagai percobaan ulang (resilience). reminder_sent_at mencegah
// pengingat dobel, jadi hanya itinerary yang sebelumnya gagal yang diproses.
Schedule::command('app:send-itinerary-reminders')->hourlyAt(0)->between('8:00', '20:00')->withoutOverlapping()->onOneServer();

Schedule::command('trusted-devices:prune')->daily()->onOneServer();

// Availability: backup database harian (dini hari, saat trafik sepi) dan
// detak jantung scheduler yang dibaca /health.
Schedule::command('app:backup-database')->dailyAt('02:00')->withoutOverlapping()->onOneServer();
Schedule::call(fn () => SystemHealth::beatScheduler())->everyMinute()->name('health-heartbeat');

// Pemantauan otomatis: kirim peringatan saat status berubah / pulih.
Schedule::command('app:health', ['--notify' => true])->everyFiveMinutes()->withoutOverlapping(10)->onOneServer();

// Kebersihan data: hasil permintaan AI dan job gagal yang sudah lama.
Schedule::command('model:prune', ['--model' => [AiTask::class]])->daily()->onOneServer();
Schedule::command('queue:prune-failed', ['--hours' => 24 * 7])->daily()->onOneServer();
