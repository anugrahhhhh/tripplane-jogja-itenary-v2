<?php

namespace App\Models;

use App\Support\FavoriteLookup;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    protected $fillable = [
        'user_id',
        'destination_id',
    ];

    protected static function booted(): void
    {
        // Favorit berubah di tengah request: buang cache status favorit
        // supaya kartu yang dirender setelahnya menampilkan status terbaru.
        static::saved(fn (Favorite $favorite) => FavoriteLookup::forget($favorite->user_id));
        static::deleted(fn (Favorite $favorite) => FavoriteLookup::forget($favorite->user_id));
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Destination, $this>
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(Destination::class);
    }
}
