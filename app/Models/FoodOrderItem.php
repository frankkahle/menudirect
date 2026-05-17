<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodOrderItem extends RestaurantModel
{
    use HasFactory;


    protected $fillable = [
        'food_order_id',
        'menu_item_id',
        'name',
        'price',
        'quantity',
        'special_requests',
        'total',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'total' => 'decimal:2',
    ];

    protected $appends = [
        'formatted_price',
        'formatted_total',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($item) {
            // Auto-calculate total if not set
            if (empty($item->total)) {
                $item->total = $item->price * $item->quantity;
            }
        });

        static::updating(function ($item) {
            // Recalculate total on update
            $item->total = $item->price * $item->quantity;
        });
    }

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(FoodOrder::class, 'food_order_id');
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * Accessors
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }

    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total, 2);
    }

    /**
     * Create from a MenuItem.
     */
    public static function fromMenuItem(MenuItem $item, int $quantity = 1, ?string $specialRequests = null): self
    {
        return new self([
            'menu_item_id' => $item->id,
            'name' => $item->name,
            'price' => $item->price,
            'quantity' => $quantity,
            'special_requests' => $specialRequests,
            'total' => $item->price * $quantity,
        ]);
    }
}
