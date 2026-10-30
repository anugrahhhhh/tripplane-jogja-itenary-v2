<?php

namespace App\Console\Commands;

use App\Models\TrustedDevice;
use Illuminate\Console\Command;

class PruneTrustedDevices extends Command
{
    protected $signature = 'trusted-devices:prune';

    protected $description = 'Hapus perangkat tepercaya yang sudah kedaluwarsa.';

    public function handle(): int
    {
        $deleted = TrustedDevice::where('expires_at', '<', now())->delete();

        $this->info("{$deleted} perangkat tepercaya kedaluwarsa dihapus.");

        return self::SUCCESS;
    }
}
