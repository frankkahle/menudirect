<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class CateringInquiry extends RestaurantModel
{
    use HasFactory;


    const STATUS_NEW = 'new';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_QUOTED = 'quoted';
    const STATUS_BOOKED = 'booked';
    const STATUS_DECLINED = 'declined';
    const STATUS_CANCELLED = 'cancelled';

    const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_CONTACTED => 'Contacted',
        self::STATUS_QUOTED => 'Quoted',
        self::STATUS_BOOKED => 'Booked',
        self::STATUS_DECLINED => 'Declined',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    const EVENT_TYPES = [
        'wedding' => 'Wedding',
        'corporate' => 'Corporate Event',
        'birthday' => 'Birthday Party',
        'anniversary' => 'Anniversary',
        'graduation' => 'Graduation',
        'holiday' => 'Holiday Party',
        'funeral' => 'Memorial/Funeral',
        'fundraiser' => 'Fundraiser',
        'other' => 'Other',
    ];

    protected $fillable = [
        'restaurant_site_id',
        'catering_package_id',
        'inquiry_number',
        'token',
        'status',
        'customer_name',
        'customer_email',
        'customer_phone',
        'event_date',
        'event_time',
        'guest_count',
        'event_type',
        'message',
        'admin_notes',
        'metadata',
        'contacted_at',
        'quoted_at',
        'booked_at',
        'declined_at',
        'cancelled_at',
    ];

    protected $casts = [
        'event_date' => 'date',
        'guest_count' => 'integer',
        'metadata' => 'array',
        'contacted_at' => 'datetime',
        'quoted_at' => 'datetime',
        'booked_at' => 'datetime',
        'declined_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    protected $appends = [
        'status_label',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($inquiry) {
            if (empty($inquiry->inquiry_number)) {
                $inquiry->inquiry_number = self::generateInquiryNumber();
            }
            if (empty($inquiry->token)) {
                $inquiry->token = self::generateToken();
            }
        });
    }

    public static function generateInquiryNumber(): string
    {
        $year = date('Y');
        $prefix = "CAT-{$year}-";

        $last = self::where('inquiry_number', 'like', $prefix . '%')
            ->orderByDesc('inquiry_number')
            ->first();

        $newNumber = $last ? (int) substr($last->inquiry_number, -5) + 1 : 1;

        return $prefix . str_pad($newNumber, 5, '0', STR_PAD_LEFT);
    }

    public static function generateToken(): string
    {
        do {
            $token = Str::random(32);
        } while (self::where('token', $token)->exists());

        return $token;
    }

    public function restaurantSite(): BelongsTo
    {
        return $this->belongsTo(RestaurantSite::class);
    }

    public function cateringPackage(): BelongsTo
    {
        return $this->belongsTo(CateringPackage::class);
    }

    // Scopes
    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [
            self::STATUS_NEW,
            self::STATUS_CONTACTED,
            self::STATUS_QUOTED,
        ]);
    }

    public function scopeBooked($query)
    {
        return $query->where('status', self::STATUS_BOOKED);
    }

    public function scopeForRestaurant($query, int $restaurantId)
    {
        return $query->where('restaurant_site_id', $restaurantId);
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'bg-yellow-100 text-yellow-800',
            self::STATUS_CONTACTED => 'bg-blue-100 text-blue-800',
            self::STATUS_QUOTED => 'bg-purple-100 text-purple-800',
            self::STATUS_BOOKED => 'bg-green-100 text-green-800',
            self::STATUS_DECLINED => 'bg-gray-100 text-gray-800',
            self::STATUS_CANCELLED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getEventTypeLabelAttribute(): ?string
    {
        return self::EVENT_TYPES[$this->event_type] ?? $this->event_type;
    }

    public function getFormattedDateAttribute(): ?string
    {
        return $this->event_date?->format('F j, Y');
    }

    public function getTrackingUrl(): string
    {
        $baseUrl = config('services.sostech.url', 'https://sos-tech.ca');
        return $baseUrl . '/catering/' . $this->token;
    }

    // Status transitions
    public function markContacted(): bool
    {
        if ($this->status !== self::STATUS_NEW) {
            return false;
        }
        $this->status = self::STATUS_CONTACTED;
        $this->contacted_at = now();
        return $this->save();
    }

    public function markQuoted(): bool
    {
        if (!in_array($this->status, [self::STATUS_NEW, self::STATUS_CONTACTED])) {
            return false;
        }
        $this->status = self::STATUS_QUOTED;
        $this->quoted_at = now();
        return $this->save();
    }

    public function markBooked(): bool
    {
        if (!in_array($this->status, [self::STATUS_CONTACTED, self::STATUS_QUOTED])) {
            return false;
        }
        $this->status = self::STATUS_BOOKED;
        $this->booked_at = now();
        return $this->save();
    }

    public function markDeclined(): bool
    {
        if (in_array($this->status, [self::STATUS_BOOKED, self::STATUS_CANCELLED, self::STATUS_DECLINED])) {
            return false;
        }
        $this->status = self::STATUS_DECLINED;
        $this->declined_at = now();
        return $this->save();
    }

    public function cancel(): bool
    {
        if (in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_DECLINED])) {
            return false;
        }
        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_at = now();
        return $this->save();
    }
}
