<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Announcement extends RestaurantModel
{
    use HasFactory;


    // Type constants
    const TYPE_SPECIAL = 'special';
    const TYPE_CLOSURE = 'closure';
    const TYPE_ALERT = 'alert';
    const TYPE_INFO = 'info';

    const TYPES = [
        self::TYPE_SPECIAL => 'Special Offer',
        self::TYPE_CLOSURE => 'Closure Notice',
        self::TYPE_ALERT => 'Alert',
        self::TYPE_INFO => 'Information',
    ];

    const TYPE_COLORS = [
        self::TYPE_SPECIAL => 'bg-green-100 text-green-800 border-green-200',
        self::TYPE_CLOSURE => 'bg-red-100 text-red-800 border-red-200',
        self::TYPE_ALERT => 'bg-yellow-100 text-yellow-800 border-yellow-200',
        self::TYPE_INFO => 'bg-blue-100 text-blue-800 border-blue-200',
    ];

    protected $fillable = [
        'restaurant_site_id',
        'message',
        'image_path',
        'title',
        'link_url',
        'type',
        'starts_at',
        'ends_at',
        'active',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::deleting(function (self $announcement) {
            if ($announcement->image_path) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($announcement->image_path);
            }
        });
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->image_path)
            : null;
    }

    protected $appends = [
        'type_label',
        'type_color_class',
        'image_url',
    ];

    /**
     * Relationships
     */
    public function restaurantSite(): BelongsTo
    {
        return $this->belongsTo(RestaurantSite::class);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeCurrentlyActive($query)
    {
        return $query->where('active', true)
            ->where(function ($q) {
                $q->whereNull('starts_at')
                  ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')
                  ->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Accessors
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function getTypeColorClassAttribute(): string
    {
        return self::TYPE_COLORS[$this->type] ?? 'bg-gray-100 text-gray-800 border-gray-200';
    }

    /**
     * Helper methods
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $this->starts_at > $now) {
            return false;
        }

        if ($this->ends_at && $this->ends_at < $now) {
            return false;
        }

        return true;
    }

    public function isScheduled(): bool
    {
        return $this->starts_at !== null || $this->ends_at !== null;
    }

    public function isPast(): bool
    {
        return $this->ends_at !== null && $this->ends_at < now();
    }

    public function isFuture(): bool
    {
        return $this->starts_at !== null && $this->starts_at > now();
    }

    /**
     * Toggle active status
     */
    public function toggleActive(): bool
    {
        $this->active = !$this->active;
        $this->save();
        return $this->active;
    }
}
