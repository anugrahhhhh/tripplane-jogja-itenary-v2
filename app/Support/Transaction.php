<?php

namespace App\Support;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Transaksi database yang otomatis diulang bila terjadi deadlock /
 * konflik konkurensi (mis. dua kolaborator menyusun ulang itinerary yang
 * sama bersamaan). Jumlah percobaan: config('resilience.db_transaction_attempts').
 */
class Transaction
{
    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    public static function run(Closure $callback): mixed
    {
        return DB::transaction($callback, max(1, (int) config('resilience.db_transaction_attempts', 3)));
    }
}
