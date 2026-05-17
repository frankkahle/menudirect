<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderNotification extends RestaurantModel
{
    use HasFactory;


    // Channel constants
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';
    const CHANNEL_WEBHOOK = 'webhook';
    const CHANNEL_DASHBOARD = 'dashboard';

    const CHANNELS = [
        self::CHANNEL_EMAIL => 'Email',
        self::CHANNEL_SMS => 'SMS',
        self::CHANNEL_WEBHOOK => 'Webhook',
        self::CHANNEL_DASHBOARD => 'Dashboard',
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';

    const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_SENT => 'Sent',
        self::STATUS_FAILED => 'Failed',
    ];

    // Notification type constants
    const TYPE_NEW_ORDER = 'new_order';
    const TYPE_ORDER_CONFIRMED = 'order_confirmed';
    const TYPE_ORDER_READY = 'order_ready';
    const TYPE_ORDER_COMPLETED = 'order_completed';
    const TYPE_ORDER_CANCELLED = 'order_cancelled';
    const TYPE_CUSTOMER_CONFIRMATION = 'customer_confirmation';
    const TYPE_CUSTOMER_STATUS_UPDATE = 'customer_status_update';

    protected $fillable = [
        'food_order_id',
        'channel',
        'recipient',
        'status',
        'notification_type',
        'sent_at',
        'error_message',
        'metadata',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'metadata' => 'array',
    ];

    /**
     * Relationships
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(FoodOrder::class, 'food_order_id');
    }

    /**
     * Scopes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeSent($query)
    {
        return $query->where('status', self::STATUS_SENT);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    /**
     * Mark as sent.
     */
    public function markSent(): bool
    {
        $this->status = self::STATUS_SENT;
        $this->sent_at = now();
        $this->error_message = null;

        return $this->save();
    }

    /**
     * Mark as failed.
     */
    public function markFailed(string $errorMessage): bool
    {
        $this->status = self::STATUS_FAILED;
        $this->error_message = $errorMessage;

        return $this->save();
    }

    /**
     * Check if notification was sent.
     */
    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    /**
     * Check if notification failed.
     */
    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Check if notification is pending.
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Get the channel label.
     */
    public function getChannelLabelAttribute(): string
    {
        return self::CHANNELS[$this->channel] ?? ucfirst($this->channel);
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }
}
