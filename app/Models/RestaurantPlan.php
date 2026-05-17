<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantPlan extends RestaurantModel
{
    use HasFactory;


    protected $fillable = [
        'name',
        'slug',
        'description',
        'price_monthly',
        'price_annual',
        'setup_fee',
        'self_service',
        'unlimited_updates',
        'online_ordering',
        'reservation_widget',
        'custom_domain',
        'analytics',
        'priority_support',
        'multi_location',
        'delivery_zones',
        'built_in_reservations',
        'catering',
        'menu_items_limit',
        'photos_limit',
        'announcements_limit',
        'features',
        'is_featured',
        'is_active',
        'sort_order',
        'online_payments_included',
        'online_payments_addon_price',
        'platform_fee_percent',
    ];

    protected $casts = [
        'price_monthly' => 'decimal:2',
        'price_annual' => 'decimal:2',
        'setup_fee' => 'decimal:2',
        'self_service' => 'boolean',
        'unlimited_updates' => 'boolean',
        'online_ordering' => 'boolean',
        'reservation_widget' => 'boolean',
        'custom_domain' => 'boolean',
        'analytics' => 'boolean',
        'priority_support' => 'boolean',
        'multi_location' => 'boolean',
        'delivery_zones' => 'boolean',
        'built_in_reservations' => 'boolean',
        'catering' => 'boolean',
        'features' => 'array',
        'is_featured' => 'boolean',
        'is_active' => 'boolean',
        'online_payments_included' => 'boolean',
        'online_payments_addon_price' => 'decimal:2',
        'platform_fee_percent' => 'decimal:2',
    ];

    /**
     * Relationships
     */
    public function sites(): HasMany
    {
        return $this->hasMany(RestaurantSite::class);
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
        return $query->orderBy('sort_order');
    }

    /**
     * Accessors
     */
    public function getFormattedMonthlyPriceAttribute(): string
    {
        return '$' . number_format($this->price_monthly, 2);
    }

    public function getFormattedAnnualPriceAttribute(): string
    {
        return '$' . number_format($this->price_annual, 2);
    }

    public function getFormattedSetupFeeAttribute(): string
    {
        return '$' . number_format($this->setup_fee, 2);
    }

    public function getAnnualSavingsAttribute(): float
    {
        return ($this->price_monthly * 12) - $this->price_annual;
    }

    public function getAnnualSavingsPercentAttribute(): int
    {
        if ($this->price_monthly <= 0) {
            return 0;
        }
        return (int) round(($this->annual_savings / ($this->price_monthly * 12)) * 100);
    }

    /**
     * Check if a limit is unlimited
     */
    public function isUnlimited(string $field): bool
    {
        return $this->{$field} === -1;
    }

    /**
     * Get display value for a limit
     */
    public function getLimitDisplay(string $field): string
    {
        $value = $this->{$field};
        return $value === -1 ? 'Unlimited' : (string) $value;
    }
}
