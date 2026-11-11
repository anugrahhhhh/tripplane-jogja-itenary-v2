<?php

namespace App\Console\Commands;

use App\Services\HealthAlerter;
use App\Services\SystemHealth;
use Illuminate\Console\Command;

/**
 * Cek kesehatan sistem dari terminal / healthcheck container.
 * Exit code 1 hanya bila komponen inti mati (status "down").
 */
class CheckHealth extends Command
{
    protected $signature = 'app:health
        {--json : Tampilkan dalam format JSON}
        {--notify : Kirim peringatan bila status berubah (dijadwalkan tiap 5 menit)}';

    protected $description = 'Periksa kesehatan database, cache, storage, antrean, scheduler, dan backup';

    public function handle(SystemHealth $health, HealthAlerter $alerter): int
    {
        $report = $health->report();

        if ($this->option('notify') && ($kind = $alerter->handle($report))) {
            $this->line($kind === 'recovered' ? 'Peringatan PULIH dikirim.' : 'Peringatan masalah dikirim.');
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(['Komponen', 'Status', 'Keterangan'], collect($report['checks'])
                ->map(fn (array $check, string $name) => [$name, strtoupper($check['status']), $check['message']])
                ->values()
                ->all());
            $this->line('Status keseluruhan: <options=bold>'.strtoupper($report['status']).'</>');
        }

        return $report['status'] === 'down' ? self::FAILURE : self::SUCCESS;
    }
}
