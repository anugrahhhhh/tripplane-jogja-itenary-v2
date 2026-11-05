<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Itinerary extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'start_date',
        'end_date',
        'traveler_count',
        'type',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'traveler_count' => 'integer',
            'reminder_sent_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<ItineraryItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(ItineraryItem::class)->orderBy('day_number')->orderBy('sort_order');
    }

    /**
     * Teman yang diundang untuk ikut melihat & menyusun itinerary ini.
     * Pemilik (user_id) tidak termasuk dalam relasi ini.
     *
     * @return BelongsToMany<User, $this>
     */
    public function collaborators(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'itinerary_collaborators')->withTimestamps();
    }

    public function isOwnedBy(?User $user): bool
    {
        return $user !== null && $this->user_id === $user->id;
    }

    /**
     * Pemilik dan seluruh kolaborator boleh melihat & menyusun itinerary.
     * Hanya pemilik yang boleh mengubah judul/tanggal, menghapus itinerary,
     * atau mengelola daftar kolaborator.
     */
    public function isAccessibleBy(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        return $this->isOwnedBy($user) || $this->collaborators->contains('id', $user->id);
    }

    public function totalDays(): int
    {
        return (int) $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Group loaded items by day_number (1..totalDays).
     *
     * @return Collection<int, Collection<int, ItineraryItem>>
     */
    public function itemsByDay(): Collection
    {
        $grouped = $this->items->groupBy('day_number');

        return collect(range(1, $this->totalDays()))
            ->mapWithKeys(fn (int $day) => [$day => $grouped->get($day, collect())]);
    }

    public function isActive(): bool
    {
        return $this->end_date->gte(today());
    }

    /**
     * Persentase hari yang sudah punya minimal satu destinasi terjadwal.
     */
    public function plannedProgress(): int
    {
        $totalDays = $this->totalDays();

        if ($totalDays === 0) {
            return 0;
        }

        $daysWithItems = $this->items->pluck('day_number')->unique()->count();

        return (int) round(($daysWithItems / $totalDays) * 100);
    }

    /**
     * Emoji representatif berdasarkan kategori destinasi yang paling sering muncul.
     */
    public function emoji(): string
    {
        $map = [
            'kuliner' => '🍜',
            'budaya-sejarah' => '🏛️',
            'ruang-publik-malam' => '🌆',
            'penginapan' => '🛏️',
            'belanja-oleh-oleh' => '🛍️',
            'kampung-wisata-kerajinan' => '🧵',
        ];

        $topCategorySlug = $this->items
            ->pluck('destination.category.slug')
            ->filter()
            ->countBy()
            ->sortDesc()
            ->keys()
            ->first();

        return $map[$topCategorySlug] ?? '🗺️';
    }
}
