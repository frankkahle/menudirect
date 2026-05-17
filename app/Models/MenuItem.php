<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class MenuItem extends RestaurantModel
{
    use HasFactory;


    protected $fillable = [
        'menu_category_id',
        'name',
        'description',
        'price',
        'price_note',
        'image_path',
        'alt_text',
        'featured',
        'sort_order',
        'active',
        'dietary_info',
        'badges',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'featured' => 'boolean',
        'active' => 'boolean',
        'sort_order' => 'integer',
        'dietary_info' => 'array',
        'badges' => 'array',
    ];

    protected $appends = [
        'formatted_price',
        'image_url',
    ];

    // Common dietary flags
    const DIETARY_VEGETARIAN = 'vegetarian';
    const DIETARY_VEGAN = 'vegan';
    const DIETARY_GLUTEN_FREE = 'gluten_free';
    const DIETARY_DAIRY_FREE = 'dairy_free';
    const DIETARY_NUT_FREE = 'nut_free';
    const DIETARY_SPICY = 'spicy';
    const DIETARY_KETO = 'keto';
    const DIETARY_LOW_CARB = 'low_carb';
    const DIETARY_HALAL = 'halal';

    const DIETARY_OPTIONS = [
        self::DIETARY_VEGETARIAN => 'Vegetarian',
        self::DIETARY_VEGAN => 'Vegan',
        self::DIETARY_GLUTEN_FREE => 'Gluten Free',
        self::DIETARY_DAIRY_FREE => 'Dairy Free',
        self::DIETARY_NUT_FREE => 'Nut Free',
        self::DIETARY_SPICY => 'Spicy',
        self::DIETARY_KETO => 'Keto-Friendly',
        self::DIETARY_LOW_CARB => 'Low Carb',
        self::DIETARY_HALAL => 'Halal',
    ];

    // Badge types
    const BADGE_POPULAR = 'popular';
    const BADGE_CHEFS_CHOICE = 'chefs_choice';
    const BADGE_NEW = 'new';
    const BADGE_SEASONAL = 'seasonal';
    const BADGE_HOUSE_SPECIAL = 'house_special';

    const BADGE_OPTIONS = [
        self::BADGE_POPULAR => ['label' => 'Popular', 'color' => 'red'],
        self::BADGE_CHEFS_CHOICE => ['label' => "Chef's Choice", 'color' => 'amber'],
        self::BADGE_NEW => ['label' => 'New', 'color' => 'green'],
        self::BADGE_SEASONAL => ['label' => 'Seasonal', 'color' => 'purple'],
        self::BADGE_HOUSE_SPECIAL => ['label' => 'House Special', 'color' => 'blue'],
    ];

    /**
     * Relationships
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MenuCategory::class, 'menu_category_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Accessors
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }
        return Storage::disk('public')->url($this->image_path);
    }

    /**
     * Helper methods
     */
    public function isActive(): bool
    {
        return $this->active === true;
    }

    public function isFeatured(): bool
    {
        return $this->featured === true;
    }

    public function hasDietaryInfo(): bool
    {
        return !empty($this->dietary_info);
    }

    public function getDietaryLabels(): array
    {
        if (!$this->dietary_info) {
            return [];
        }

        return array_filter(
            array_map(
                fn($key) => self::DIETARY_OPTIONS[$key] ?? null,
                $this->dietary_info
            )
        );
    }

    public function hasBadges(): bool
    {
        return !empty($this->badges);
    }

    public function getBadgeData(): array
    {
        if (!$this->badges) {
            return [];
        }

        return array_filter(
            array_map(
                fn($key) => self::BADGE_OPTIONS[$key] ?? null,
                $this->badges
            )
        );
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(): bool
    {
        $this->featured = !$this->featured;
        $this->save();
        return $this->featured;
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
