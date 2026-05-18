<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CateringPackage extends RestaurantModel
{
    use HasFactory;


    protected $fillable = [
        'restaurant_site_id',
        'name',
        'description',
        'price',
        'price_type',
        'min_guests',
        'max_guests',
        'lead_time_hours',
        'includes',
        'image_path',
        'active',
        'sort_order',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'active' => 'boolean',
        'sort_order' => 'integer',
        'includes' => 'array',
        'min_guests' => 'integer',
        'max_guests' => 'integer',
        'lead_time_hours' => 'integer',
    ];

    protected $appends = [
        'formatted_price',
        'image_url',
    ];

    public function restaurantSite(): BelongsTo
    {
        return $this->belongsTo(RestaurantSite::class);
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(CateringInquiry::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    public function getFormattedPriceAttribute(): string
    {
        $price = '$' . number_format($this->price, 2);
        return $this->price_type === 'per_person' ? $price . '/person' : $price;
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }
        return Storage::disk('public')->url($this->image_path);
    }

    public function getPriceTypeLabelAttribute(): string
    {
        return $this->price_type === 'per_person' ? 'Per Person' : 'Flat Rate';
    }
}
