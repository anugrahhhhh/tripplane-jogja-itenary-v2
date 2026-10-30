<?php

namespace App\Models;

use App\Services\OperatingHoursService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Destination extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'slug',
        'description',
        'recommendation_reason',
        'address',
        'latitude',
        'longitude',
        'price_min',
        'price_max',
        'open_time',
        'close_time',
        'closed_days',
        'cover_photo',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'price_min' => 'integer',
            'price_max' => 'integer',
            'closed_days' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<DestinationPhoto, $this>
     */
    public function photos(): HasMany
    {
        return $this->hasMany(DestinationPhoto::class);
    }

    /**
     * @return HasMany<Favorite, $this>
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * @return HasMany<Review, $this>
     */
    public function visibleReviews(): HasMany
    {
        return $this->reviews()->where('is_visible', true);
    }

    /**
     * @return HasMany<ItineraryItem, $this>
     */
    public function itineraryItems(): HasMany
    {
        return $this->hasMany(ItineraryItem::class);
    }

    public function averageRating(): float
    {
        return round((float) $this->visibleReviews()->avg('rating'), 1);
    }

    public function reviewsCount(): int
    {
        return $this->visibleReviews()->count();
    }

    public function isFavoritedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->favorites()->where('user_id', $user->id)->exists();
    }

    public function isOpenNow(): bool
    {
        return OperatingHoursService::isOpenAt($this->open_time, $this->close_time, now());
    }
}
