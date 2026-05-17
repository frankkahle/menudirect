<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RestaurantLead extends RestaurantModel
{

    use HasFactory, SoftDeletes;

    const STATUS_NEW = 'new';
    const STATUS_CONTACTED = 'contacted';
    const STATUS_INTERESTED = 'interested';
    const STATUS_QUOTED = 'quoted';
    const STATUS_CONVERTED = 'converted';
    const STATUS_DECLINED = 'declined';
    const STATUS_DO_NOT_CONTACT = 'do_not_contact';

    const STATUSES = [
        self::STATUS_NEW => 'New',
        self::STATUS_CONTACTED => 'Contacted',
        self::STATUS_INTERESTED => 'Interested',
        self::STATUS_QUOTED => 'Quoted',
        self::STATUS_CONVERTED => 'Converted',
        self::STATUS_DECLINED => 'Declined',
        self::STATUS_DO_NOT_CONTACT => 'Do Not Contact',
    ];

    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    const PRIORITIES = [
        self::PRIORITY_LOW => 'Low',
        self::PRIORITY_MEDIUM => 'Medium',
        self::PRIORITY_HIGH => 'High',
        self::PRIORITY_URGENT => 'Urgent',
    ];

    const SOURCE_GNB = 'gnb_inspections';
    const SOURCE_MANUAL = 'manual';
    const SOURCE_IMPORT = 'import';
    const SOURCE_DEMO_SANDBOX = 'demo_sandbox';

    protected $fillable = [
        'source',
        'source_id',
        'business_name',
        'address',
        'city',
        'province',
        'postal_code',
        'phone',
        'email',
        'website',
        'latitude',
        'longitude',
        'owner_name',
        'operator_name',
        'facility_type',
        'inspection_rating',
        'last_inspection_date',
        'status',
        'priority',
        'tags',
        'notes',
        'next_follow_up_at',
        'converted_restaurant_site_id',
        'contacted_at',
        'last_activity_at',
    ];

    protected $casts = [
        'tags' => 'array',
        'last_inspection_date' => 'date',
        'next_follow_up_at' => 'datetime',
        'contacted_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    protected $appends = [
        'status_label',
        'status_badge_class',
        'priority_label',
        'priority_badge_class',
    ];

    // ── Relationships ──

    public function activities(): HasMany
    {
        return $this->hasMany(LeadActivity::class)->orderByDesc('created_at');
    }

    public function convertedSite(): BelongsTo
    {
        return $this->belongsTo(RestaurantSite::class, 'converted_restaurant_site_id');
    }

    public function emailTracks(): HasMany
    {
        return $this->hasMany(LeadEmailTrack::class)->orderByDesc('sent_at');
    }

    // ── Scopes ──

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeCity($query, string $city)
    {
        return $query->where('city', $city);
    }

    public function scopePriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeDueForFollowUp($query)
    {
        return $query->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<=', now())
            ->whereNotIn('status', [self::STATUS_CONVERTED, self::STATUS_DECLINED, self::STATUS_DO_NOT_CONTACT]);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CONVERTED, self::STATUS_DECLINED, self::STATUS_DO_NOT_CONTACT]);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('business_name', 'like', "%{$term}%")
              ->orWhere('city', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('owner_name', 'like', "%{$term}%")
              ->orWhere('address', 'like', "%{$term}%");
        });
    }

    // ── Accessors ──

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_NEW => 'bg-blue-100 text-blue-800',
            self::STATUS_CONTACTED => 'bg-yellow-100 text-yellow-800',
            self::STATUS_INTERESTED => 'bg-purple-100 text-purple-800',
            self::STATUS_QUOTED => 'bg-indigo-100 text-indigo-800',
            self::STATUS_CONVERTED => 'bg-green-100 text-green-800',
            self::STATUS_DECLINED => 'bg-red-100 text-red-800',
            self::STATUS_DO_NOT_CONTACT => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst($this->priority);
    }

    public function getPriorityBadgeClassAttribute(): string
    {
        return match ($this->priority) {
            self::PRIORITY_LOW => 'bg-gray-100 text-gray-800',
            self::PRIORITY_MEDIUM => 'bg-blue-100 text-blue-800',
            self::PRIORITY_HIGH => 'bg-orange-100 text-orange-800',
            self::PRIORITY_URGENT => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->address,
            $this->city,
            $this->province,
            $this->postal_code,
        ]);
        return implode(', ', $parts);
    }

    public function getGoogleMapsUrlAttribute(): ?string
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.google.com/maps?q={$this->latitude},{$this->longitude}";
        }
        if ($this->full_address) {
            return "https://www.google.com/maps?q=" . urlencode($this->full_address);
        }
        return null;
    }
}
