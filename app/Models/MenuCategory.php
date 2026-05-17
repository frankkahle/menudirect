<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuCategory extends RestaurantModel
{
    use HasFactory;


    protected $fillable = [
        'restaurant_site_id',
        'name',
        'description',
        'sort_order',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Relationships
     */
    public function restaurantSite(): BelongsTo
    {
        return $this->belongsTo(RestaurantSite::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('sort_order');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Helper methods
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    /**
     * Get active items count
     */
    public function activeItemsCount(): int
    {
        return $this->items()->where('active', true)->count();
    }

    /**
     * Get the next sort order value for items in this category
     */
    public function getNextItemSortOrder(): int
    {
        return ($this->items()->max('sort_order') ?? 0) + 1;
    }
}
