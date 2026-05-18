<?php

namespace App\Models;

use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftCloseout extends RestaurantModel
{

    protected $fillable = [
        'restaurant_site_id',
        'closed_by_staff_id',
        'shift_date',
        'shift_started_at',
        'shift_closed_at',
        'total_orders',
        'dine_in_orders',
        'pickup_orders',
        'delivery_orders',
        'cancelled_orders',
        'gross_sales',
        'tax_collected',
        'delivery_fees',
        'total_revenue',
        'cash_total',
        'card_total',
        'platform_fees',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'shift_started_at' => 'datetime',
        'shift_closed_at' => 'datetime',
        'gross_sales' => 'decimal:2',
        'tax_collected' => 'decimal:2',
        'delivery_fees' => 'decimal:2',
        'total_revenue' => 'decimal:2',
        'cash_total' => 'decimal:2',
        'card_total' => 'decimal:2',
        'platform_fees' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function restaurantSite(): BelongsTo
    {
        return $this->belongsTo(RestaurantSite::class);
    }

    public function closedBy(): BelongsTo
    {
        return $this->belongsTo(RestaurantStaff::class, 'closed_by_staff_id');
    }

    /**
     * Generate a closeout from today's orders.
     */
    public static function generateForDate(RestaurantSite $site, string $date, ?int $staffId = null, ?string $notes = null): self
    {
        $orders = FoodOrder::where('restaurant_site_id', $site->id)
            ->whereDate('created_at', $date)
            ->get();

        $completed = $orders->whereIn('status', [FoodOrder::STATUS_COMPLETED, FoodOrder::STATUS_READY, FoodOrder::STATUS_CONFIRMED, FoodOrder::STATUS_PREPARING]);
        $cancelled = $orders->where('status', FoodOrder::STATUS_CANCELLED);

        $grossSales = $completed->sum('subtotal');
        $taxCollected = $completed->sum('tax_amount');
        $deliveryFees = $completed->sum('delivery_fee');
        $totalRevenue = $completed->sum('total');

        $cashTotal = $completed->where('payment_status', '!=', 'paid')->sum('total');
        $cardTotal = $completed->where('payment_status', 'paid')->sum('total');
        $platformFees = $completed->sum('platform_fee_cents') / 100;

        return self::create([
            'restaurant_site_id' => $site->id,
            'closed_by_staff_id' => $staffId,
            'shift_date' => $date,
            'shift_started_at' => $orders->min('created_at'),
            'shift_closed_at' => now(),
            'total_orders' => $completed->count(),
            'dine_in_orders' => $completed->where('order_type', FoodOrder::TYPE_DINE_IN)->count(),
            'pickup_orders' => $completed->where('order_type', FoodOrder::TYPE_PICKUP)->count(),
            'delivery_orders' => $completed->where('order_type', FoodOrder::TYPE_DELIVERY)->count(),
            'cancelled_orders' => $cancelled->count(),
            'gross_sales' => $grossSales,
            'tax_collected' => $taxCollected,
            'delivery_fees' => $deliveryFees,
            'total_revenue' => $totalRevenue,
            'cash_total' => $cashTotal,
            'card_total' => $cardTotal,
            'platform_fees' => $platformFees,
            'notes' => $notes,
        ]);
    }
}
