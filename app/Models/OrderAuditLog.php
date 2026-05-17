<?php

namespace App\Models;

use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderAuditLog extends RestaurantModel
{

    protected $fillable = [
        'food_order_id',
        'staff_id',
        'action',
        'from_status',
        'to_status',
        'source',
        'note',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(FoodOrder::class, 'food_order_id');
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(RestaurantStaff::class, 'staff_id');
    }

    /**
     * Log an order event.
     */
    public static function log(FoodOrder $order, string $action, array $extra = []): self
    {
        return self::create(array_merge([
            'food_order_id' => $order->id,
            'action' => $action,
        ], $extra));
    }
}
