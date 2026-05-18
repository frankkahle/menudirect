<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FoodOrder extends RestaurantModel
{
    use HasFactory;


    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PREPARING = 'preparing';
    const STATUS_READY = 'ready';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_PREPARING => 'Preparing',
        self::STATUS_READY => 'Ready',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    // Order type constants
    const TYPE_PICKUP = 'pickup';
    const TYPE_DELIVERY = 'delivery';
    const TYPE_DINE_IN = 'dine_in';

    const ORDER_TYPES = [
        self::TYPE_PICKUP => 'Pickup',
        self::TYPE_DELIVERY => 'Delivery',
        self::TYPE_DINE_IN => 'Dine-in',
    ];

    protected $fillable = [
        'restaurant_site_id',
        'staff_id',
        'order_number',
        'token',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'order_type',
        'table_number',
        'scheduled_for',
        'is_asap',
        'delivery_address',
        'delivery_latitude',
        'delivery_longitude',
        'delivery_distance_km',
        'delivery_zone_name',
        'estimated_delivery_minutes',
        'special_instructions',
        'subtotal',
        'tax_amount',
        'tax_rate',
        'delivery_fee',
        'total',
        'estimated_ready_at',
        'confirmed_at',
        'ready_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'metadata',
        'payment_status',
        'payment_intent_id',
        'platform_fee_cents',
        'paid_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'tax_rate' => 'decimal:4',
        'delivery_fee' => 'decimal:2',
        'delivery_latitude' => 'decimal:7',
        'delivery_longitude' => 'decimal:7',
        'delivery_distance_km' => 'decimal:2',
        'total' => 'decimal:2',
        'estimated_ready_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'ready_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'scheduled_for' => 'datetime',
        'is_asap' => 'boolean',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'platform_fee_cents' => 'integer',
    ];

    protected $appends = [
        'status_label',
        'order_type_label',
        'formatted_total',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
            if (empty($order->token)) {
                $order->token = self::generateToken();
            }
        });

        static::created(function ($order) {
            OrderAuditLog::log($order, 'created', [
                'to_status' => $order->status,
                'staff_id' => $order->staff_id,
                'source' => $order->staff_id ? 'staff' : 'customer',
                'metadata' => [
                    'order_type' => $order->order_type,
                    'table_number' => $order->table_number,
                    'total' => (float) $order->total,
                ],
            ]);
        });

        static::updating(function ($order) {
            if ($order->isDirty('status')) {
                OrderAuditLog::log($order, $order->status, [
                    'from_status' => $order->getOriginal('status'),
                    'to_status' => $order->status,
                    'source' => 'system',
                ]);
            }
        });
    }

    /**
     * Generate a unique order number with retry on collision.
     */
    public static function generateOrderNumber(): string
    {
        $year = date('Y');
        $prefix = "ORD-{$year}-";

        for ($attempt = 0; $attempt < 5; $attempt++) {
            $lastOrder = self::where('order_number', 'like', $prefix . '%')
                ->lockForUpdate()
                ->orderByDesc('order_number')
                ->first();

            $newNumber = $lastOrder
                ? (int) substr($lastOrder->order_number, -5) + 1
                : 1;

            $orderNumber = $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);

            if (!self::where('order_number', $orderNumber)->exists()) {
                return $orderNumber;
            }
        }

        // Fallback: append random suffix to guarantee uniqueness
        return $prefix . str_pad(random_int(10000, 99999), 5, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a unique tracking token.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    /**
     * Relationships
     */
    public function restaurantSite(): BelongsTo
    {
        return $this->belongsTo(RestaurantSite::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(RestaurantStaff::class, 'staff_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(FoodOrderItem::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(OrderNotification::class);
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(OrderAuditLog::class);
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeConfirmed($query)
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopePreparing($query)
    {
        return $query->where('status', self::STATUS_PREPARING);
    }

    public function scopeReady($query)
    {
        return $query->where('status', self::STATUS_READY);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PREPARING,
            self::STATUS_READY,
        ]);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_site_id', $restaurantId);
    }

    /**
     * Accessors
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getOrderTypeLabelAttribute(): string
    {
        return self::ORDER_TYPES[$this->order_type] ?? ucfirst($this->order_type);
    }

    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total, 2);
    }

    public function getFormattedSubtotalAttribute(): string
    {
        return '$' . number_format($this->subtotal, 2);
    }

    public function getFormattedTaxAttribute(): string
    {
        return '$' . number_format($this->tax_amount, 2);
    }

    public function getFormattedDeliveryFeeAttribute(): string
    {
        return '$' . number_format($this->delivery_fee, 2);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_CONFIRMED => 'bg-blue-100 text-blue-800',
            self::STATUS_PREPARING => 'bg-purple-100 text-purple-800',
            self::STATUS_READY => 'bg-green-100 text-green-800',
            self::STATUS_COMPLETED => 'bg-gray-100 text-gray-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Status transition methods
     */
    public function confirm(?int $prepTimeMinutes = null): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_CONFIRMED;
        $this->confirmed_at = now();

        if ($prepTimeMinutes) {
            $this->estimated_ready_at = now()->addMinutes($prepTimeMinutes);
        }

        return $this->save();
    }

    public function startPreparing(): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED])) {
            return false;
        }

        $this->status = self::STATUS_PREPARING;
        if (!$this->confirmed_at) {
            $this->confirmed_at = now();
        }

        return $this->save();
    }

    public function markReady(): bool
    {
        if (!in_array($this->status, [self::STATUS_CONFIRMED, self::STATUS_PREPARING])) {
            return false;
        }

        $this->status = self::STATUS_READY;
        $this->ready_at = now();

        return $this->save();
    }

    public function complete(): bool
    {
        if ($this->status !== self::STATUS_READY) {
            return false;
        }

        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();

        return $this->save();
    }

    public function cancel(?string $reason = null): bool
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED])) {
            return false;
        }

        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;

        return $this->save();
    }

    /**
     * Helper methods
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isConfirmed(): bool
    {
        return $this->status === self::STATUS_CONFIRMED;
    }

    public function isPreparing(): bool
    {
        return $this->status === self::STATUS_PREPARING;
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isActive(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PREPARING,
            self::STATUS_READY,
        ]);
    }

    public function isDelivery(): bool
    {
        return $this->order_type === self::TYPE_DELIVERY;
    }

    public function isPickup(): bool
    {
        return $this->order_type === self::TYPE_PICKUP;
    }

    public function isDineIn(): bool
    {
        return $this->order_type === self::TYPE_DINE_IN;
    }

    public function isStaffOrder(): bool
    {
        return $this->staff_id !== null;
    }

    public function isScheduled(): bool
    {
        return !$this->is_asap && $this->scheduled_for !== null;
    }

    public function getScheduledTimeLabel(): ?string
    {
        if (!$this->isScheduled()) {
            return null;
        }

        $scheduledFor = $this->scheduled_for;
        if ($scheduledFor->isToday()) {
            return 'Today at ' . $scheduledFor->format('g:i A');
        } elseif ($scheduledFor->isTomorrow()) {
            return 'Tomorrow at ' . $scheduledFor->format('g:i A');
        }

        return $scheduledFor->format('l, M j \a\t g:i A');
    }

    /**
     * Get the public tracking URL for this order.
     */
    public function getTrackingUrl(): string
    {
        $baseUrl = config('app.url', 'https://portal.menudirect.ca');
        return $baseUrl . '/order/' . $this->token;
    }

    /**
     * Calculate totals from items.
     */
    public function calculateTotals(): void
    {
        $subtotal = $this->items->sum('total');
        $taxAmount = $subtotal * $this->tax_rate;
        $total = $subtotal + $taxAmount + $this->delivery_fee;

        $this->subtotal = $subtotal;
        $this->tax_amount = $taxAmount;
        $this->total = $total;
    }

    /**
     * Get item count.
     */
    public function getItemCountAttribute(): int
    {
        return $this->items->sum('quantity');
    }
}
