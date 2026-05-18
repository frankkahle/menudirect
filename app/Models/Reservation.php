<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Reservation extends RestaurantModel
{
    use HasFactory;


    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_SEATED = 'seated';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_NO_SHOW = 'no_show';

    const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_CONFIRMED => 'Confirmed',
        self::STATUS_SEATED => 'Seated',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
        self::STATUS_NO_SHOW => 'No Show',
    ];

    protected $fillable = [
        'restaurant_site_id',
        'confirmation_number',
        'token',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'reservation_date',
        'reservation_time',
        'party_size',
        'duration_minutes',
        'special_requests',
        'confirmed_at',
        'seated_at',
        'completed_at',
        'cancelled_at',
        'cancellation_reason',
        'cancelled_by',
        'metadata',
    ];

    protected $casts = [
        'reservation_date' => 'date',
        'confirmed_at' => 'datetime',
        'seated_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $appends = [
        'status_label',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($reservation) {
            if (empty($reservation->confirmation_number)) {
                $reservation->confirmation_number = self::generateConfirmationNumber();
            }
            if (empty($reservation->token)) {
                $reservation->token = self::generateToken();
            }
        });
    }

    /**
     * Generate a unique confirmation number.
     */
    public static function generateConfirmationNumber(): string
    {
        $date = date('Ymd');
        $prefix = "RES-{$date}-";

        do {
            $number = $prefix . strtoupper(Str::random(5));
        } while (self::where('confirmation_number', $number)->exists());

        return $number;
    }

    /**
     * Generate a unique token.
     */
    public static function generateToken(): string
    {
        do {
            $token = Str::random(64);
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

    public function scopeForDate($query, $date)
    {
        return $query->whereDate('reservation_date', $date);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_SEATED,
        ]);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('reservation_date', '>=', today())
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED])
            ->orderBy('reservation_date')
            ->orderBy('reservation_time');
    }

    /**
     * Accessors
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_CONFIRMED => 'bg-blue-100 text-blue-800',
            self::STATUS_SEATED => 'bg-purple-100 text-purple-800',
            self::STATUS_COMPLETED => 'bg-green-100 text-green-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            self::STATUS_NO_SHOW => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getFormattedTimeAttribute(): string
    {
        return \Carbon\Carbon::parse($this->reservation_time)->format('g:i A');
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->reservation_date->format('l, F j, Y');
    }

    /**
     * Status transition methods
     */
    public function confirm(): bool
    {
        if ($this->status !== self::STATUS_PENDING) {
            return false;
        }

        $this->status = self::STATUS_CONFIRMED;
        $this->confirmed_at = now();

        return $this->save();
    }

    public function seat(): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED])) {
            return false;
        }

        $this->status = self::STATUS_SEATED;
        $this->seated_at = now();
        if (!$this->confirmed_at) {
            $this->confirmed_at = now();
        }

        return $this->save();
    }

    public function complete(): bool
    {
        if ($this->status !== self::STATUS_SEATED) {
            return false;
        }

        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();

        return $this->save();
    }

    public function cancel(?string $reason = null, ?string $by = null): bool
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_NO_SHOW])) {
            return false;
        }

        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_at = now();
        $this->cancellation_reason = $reason;
        $this->cancelled_by = $by;

        return $this->save();
    }

    public function markNoShow(): bool
    {
        if (!in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED])) {
            return false;
        }

        $this->status = self::STATUS_NO_SHOW;

        return $this->save();
    }

    /**
     * Get the public status page URL.
     */
    public function getStatusUrl(): string
    {
        $baseUrl = config('app.url', 'https://portal.menudirect.ca');
        return $baseUrl . '/reservation/' . $this->token;
    }
}
