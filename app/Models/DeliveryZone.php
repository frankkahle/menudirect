<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryZone extends RestaurantModel
{
    use HasFactory;


    protected $fillable = [
        'restaurant_site_id',
        'name',
        'radius_km',
        'delivery_fee',
        'minimum_order',
        'estimated_delivery_minutes',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'radius_km' => 'decimal:2',
        'delivery_fee' => 'decimal:2',
        'minimum_order' => 'decimal:2',
        'is_active' => 'boolean',
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
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('radius_km', 'asc');
    }
}
