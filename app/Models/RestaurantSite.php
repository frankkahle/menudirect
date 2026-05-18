<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use App\Models\RestaurantPlan;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class RestaurantSite extends RestaurantModel
{

    use HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::addGlobalScope("notArchived", function ($query) {
            $query->whereNull("archived_at");
        });
    }

    // Status constants
    const STATUS_DEMO = 'demo';
    const STATUS_ACTIVE = 'active';
    const STATUS_SUSPENDED = 'suspended';

    // Plan constants
    const PLAN_BASIC = 'basic';
    const PLAN_SELFSERVICE = 'selfservice';
    const PLAN_PREMIUM = 'premium';
    const PLAN_MAX = 'max';

    const STATUSES = [
        self::STATUS_DEMO => 'Demo',
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_SUSPENDED => 'Suspended',
    ];

    const TEMPLATES = [
        'restaurant' => 'Classic (Default)',
        'bistro' => 'Bistro (Fine Dining)',
        'counter' => 'Counter (Fast Casual)',
        'heritage' => 'Heritage (Family / Diner)',
    ];

    const PLANS = [
        self::PLAN_BASIC => 'Basic',
        self::PLAN_SELFSERVICE => 'Self-Service',
        self::PLAN_PREMIUM => 'Premium',
        self::PLAN_MAX => 'Max',
    ];

    protected $fillable = [
        'client_id',
        'restaurant_plan_id',
        'parent_site_id',
        'is_primary_location',
        'slug',
        'custom_domain',
        'business_name',
        'tagline',
        'seo_title',
        'seo_description',
        'seo_keywords',
        'cuisine_type',
        'price_range',
        'phone',
        'email',
        'address',
        'latitude',
        'longitude',
        'hours',
        'colors',
        'settings',
        'social_proof',
        'logo_path',
        'cover_photo_path',
        'og_image_path',
        'google_place_id',
        'timezone',
        'force_closed',
        'closure_message',
        'status',
        'plan',
        'published_at',
        'ordering_enabled',
        'ordering_settings',
        'reservation_settings',
        'catering_enabled',
        'catering_settings',
        'stripe_account_id',
        'stripe_account_status',
        'stripe_charges_enabled',
        'stripe_payouts_enabled',
        'stripe_onboarded_at',
        'online_payments_enabled',
    ];

    protected $casts = [
        'hours' => 'array',
        'colors' => 'array',
        'settings' => 'array',
        'social_proof' => 'array',
        'published_at' => 'datetime',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'ordering_enabled' => 'boolean',
        'force_closed' => 'boolean',
        'ordering_settings' => 'array',
        'reservation_settings' => 'array',
        'catering_enabled' => 'boolean',
        'catering_settings' => 'array',
        'is_primary_location' => 'boolean',
        'stripe_charges_enabled' => 'boolean',
        'stripe_payouts_enabled' => 'boolean',
        'stripe_onboarded_at' => 'datetime',
        'online_payments_enabled' => 'boolean',
    ];

    protected $appends = [
        'status_label',
        'plan_label',
    ];

    // Default colors
    const DEFAULT_COLORS = [
        'primary' => '#2563eb',
        'secondary' => '#7c3aed',
        'accent' => '#f59e0b',
    ];

    // Default hours
    const DEFAULT_HOURS = [
        'Monday' => '11:00 AM - 9:00 PM',
        'Tuesday' => '11:00 AM - 9:00 PM',
        'Wednesday' => '11:00 AM - 9:00 PM',
        'Thursday' => '11:00 AM - 9:00 PM',
        'Friday' => '11:00 AM - 10:00 PM',
        'Saturday' => '11:00 AM - 10:00 PM',
        'Sunday' => '12:00 PM - 8:00 PM',
    ];

    /**
     * Relationships
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function restaurantPlan(): BelongsTo
    {
        return $this->belongsTo(RestaurantPlan::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(MenuCategory::class)->orderBy('sort_order');
    }

    /**
     * Get the restaurant's current plan via the linked service.
     */
    public function currentPlan(): ?RestaurantPlan
    {
        if ($this->restaurant_plan_id) {
            return $this->restaurantPlan;
        }

        // Fall back to the Service record
        $service = \App\Models\Service::where('serviceable_type', self::class)
            ->where('serviceable_id', $this->id)
            ->where('status', 'active')
            ->first();

        if ($service && $service->metadata['plan_id'] ?? null) {
            return RestaurantPlan::find($service->metadata['plan_id']);
        }

        return null;
    }

    /**
     * Can this restaurant enable online payments on its current plan?
     * Returns false for Basic and SiteFresh; true for Pro and Max.
     */
    public function canEnableOnlinePayments(): bool
    {
        $plan = $this->currentPlan();
        if (!$plan) return false;

        return in_array($plan->slug, ['sitefresh-pro', 'menudirect-max']);
    }

    /**
     * Does this restaurant need to pay the $10/mo addon to enable online payments?
     * False if included in plan (Max) or plan doesn't support it (Basic, SiteFresh).
     */
    public function onlinePaymentsRequiresAddon(): bool
    {
        $plan = $this->currentPlan();
        if (!$plan) return false;

        return $plan->slug === 'sitefresh-pro';
    }

    /**
     * Platform fee percentage for this restaurant's plan (e.g., 1.00 for 1%).
     */
    public function platformFeePercent(): float
    {
        $plan = $this->currentPlan();
        return (float) ($plan->platform_fee_percent ?? 0);
    }

    public function staff(): HasMany
    {
        return $this->hasMany(RestaurantStaff::class, 'restaurant_site_id');
    }

    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class);
    }

    public function service(): MorphOne
    {
        return $this->morphOne(Service::class, 'serviceable');
    }

    public function holidayHours(): HasMany
    {
        return $this->hasMany(HolidayHour::class)->orderBy('date');
    }

    public function foodOrders(): HasMany
    {
        return $this->hasMany(FoodOrder::class);
    }

    public function deliveryZones(): HasMany
    {
        return $this->hasMany(DeliveryZone::class)->orderBy('radius_km');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function cateringPackages(): HasMany
    {
        return $this->hasMany(CateringPackage::class)->orderBy('sort_order');
    }

    public function cateringInquiries(): HasMany
    {
        return $this->hasMany(CateringInquiry::class);
    }

    public function parentSite(): BelongsTo
    {
        return $this->belongsTo(RestaurantSite::class, 'parent_site_id');
    }

    public function childSites(): HasMany
    {
        return $this->hasMany(RestaurantSite::class, 'parent_site_id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeDemo($query)
    {
        return $query->where('status', self::STATUS_DEMO);
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Accessors
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst($this->status);
    }

    public function getPlanLabelAttribute(): string
    {
        return self::PLANS[$this->plan] ?? ucfirst($this->plan);
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'bg-green-100 text-green-800',
            self::STATUS_DEMO => 'bg-yellow-100 text-yellow-800',
            self::STATUS_SUSPENDED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }
        return Storage::disk('public')->url($this->logo_path);
    }

    public function getCoverPhotoUrlAttribute(): ?string
    {
        if (!$this->cover_photo_path) {
            return null;
        }
        return Storage::disk('public')->url($this->cover_photo_path);
    }

    /**
     * Helper methods
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isDemo(): bool
    {
        return $this->status === self::STATUS_DEMO;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isDemoSite(): bool
    {
        return $this->client && $this->client->isDemoAccount();
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function getPublicUrl(): string
    {
        // Custom domain wins if configured; otherwise restaurants live at slug.menudirect.ca
        if ($this->custom_domain) {
            return 'https://' . $this->custom_domain;
        }
        $primary = $this->customDomains()->where('is_primary', true)->where('status', 'active')->value('domain');
        if ($primary) {
            return 'https://' . $primary;
        }
        return 'https://' . $this->slug . '.menudirect.ca';
    }

    public function getColors(): array
    {
        return array_merge(self::DEFAULT_COLORS, $this->colors ?? []);
    }

    public function getHours(): array
    {
        return $this->hours ?? self::DEFAULT_HOURS;
    }

    /**
     * Resolve sister site slugs into displayable cards.
     * Sister sites are stored in settings.sister_sites as an array of slugs.
     * Only active/demo sister sites are included.
     */
    public function getSisterSitesData(): array
    {
        $slugs = $this->settings['sister_sites'] ?? [];
        if (empty($slugs) || !is_array($slugs)) {
            return [];
        }

        $sites = self::whereIn('slug', $slugs)
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_DEMO])
            ->get();

        return $sites->map(function ($site) {
            // Prefer custom domain if set, else menudirect subdomain
            $url = $site->custom_domain
                ? 'https://' . $site->custom_domain
                : 'https://' . $site->slug . '.menudirect.ca';

            return [
                'slug' => $site->slug,
                'name' => $site->business_name,
                'tagline' => $site->tagline,
                'logo' => $site->logo_path ? Storage::disk('public')->url($site->logo_path) : null,
                'cover' => $site->cover_photo_path ? Storage::disk('public')->url($site->cover_photo_path) : null,
                'cuisine' => $site->cuisine_type,
                'colors' => $site->getColors(),
                'url' => $url,
            ];
        })->toArray();
    }

    /**
     * Get hours for a specific date, checking holiday hours first.
     * Date is interpreted in the restaurant's local timezone.
     */
    public function getHoursForDate(\DateTimeInterface|string $date): ?string
    {
        $tz = $this->getTimezone();
        if (is_string($date)) {
            $date = \Carbon\Carbon::parse($date, $tz);
        } else {
            $date = \Carbon\Carbon::instance($date)->setTimezone($tz);
        }

        // Check for holiday hours
        $holiday = $this->holidayHours()
            ->whereDate('date', $date->format('Y-m-d'))
            ->first();

        if ($holiday) {
            return $holiday->hours;
        }

        // Return regular hours for the day
        $dayName = $date->format('l');
        $hours = $this->getHours();

        return $hours[$dayName] ?? null;
    }

    /**
     * Check if restaurant is open on a given date.
     */
    public function isOpenOnDate(\DateTimeInterface|string $date): bool
    {
        $hours = $this->getHoursForDate($date);

        if (!$hours) {
            return false;
        }

        return strtolower($hours) !== 'closed';
    }

    /**
     * Parse hours string like "11:00 AM - 9:00 PM" into open/close times.
     */
    public function parseHoursString(string $hours): ?array
    {
        if (strtolower($hours) === 'closed') {
            return null;
        }

        // Handle formats like "11:00 AM - 9:00 PM" or "11:00AM-9:00PM"
        if (preg_match('/(\d{1,2}:\d{2}\s*(?:AM|PM)?)\s*-\s*(\d{1,2}:\d{2}\s*(?:AM|PM)?)/i', $hours, $matches)) {
            try {
                $open = \Carbon\Carbon::parse($matches[1]);
                $close = \Carbon\Carbon::parse($matches[2]);

                // If close time is before open time, it means it closes after midnight
                if ($close->lt($open)) {
                    $close->addDay();
                }

                return [
                    'open' => $open->format('H:i'),
                    'close' => $close->format('H:i'),
                ];
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * The restaurant's timezone (IANA string). Falls back to Atlantic.
     */
    public function getTimezone(): string
    {
        return $this->timezone ?: 'America/Halifax';
    }

    /**
     * Check if restaurant is currently open (in its local timezone).
     * Respects the force_closed override (seasonal closure, emergency closure, etc).
     */
    public function isCurrentlyOpen(): bool
    {
        if ($this->force_closed) return false;
        return $this->isOpenAt(now($this->getTimezone()));
    }

    /**
     * Check if restaurant is open at a specific datetime.
     * The datetime is always interpreted in the restaurant's local timezone.
     */
    public function isOpenAt(\DateTimeInterface|string $datetime): bool
    {
        // Force-closed blocks both immediate and scheduled orders
        if ($this->force_closed) return false;

        $tz = $this->getTimezone();
        if (is_string($datetime)) {
            $datetime = \Carbon\Carbon::parse($datetime, $tz);
        } else {
            $datetime = \Carbon\Carbon::instance($datetime)->setTimezone($tz);
        }

        $hours = $this->getHoursForDate($datetime);

        if (!$hours || strtolower($hours) === 'closed') {
            return false;
        }

        $parsed = $this->parseHoursString($hours);
        if (!$parsed) {
            return false;
        }

        $currentTime = $datetime->format('H:i');

        return $currentTime >= $parsed['open'] && $currentTime <= $parsed['close'];
    }

    /**
     * Get the next available ordering time.
     */
    public function getNextOpenTime(): ?\Carbon\Carbon
    {
        $now = now();

        // Check if open now
        if ($this->isCurrentlyOpen()) {
            return $now;
        }

        // Check today's remaining hours
        $todayHours = $this->getHoursForDate($now);
        if ($todayHours && strtolower($todayHours) !== 'closed') {
            $parsed = $this->parseHoursString($todayHours);
            if ($parsed) {
                $openTime = \Carbon\Carbon::parse($parsed['open']);
                if ($now->format('H:i') < $parsed['open']) {
                    return $now->copy()->setTimeFromTimeString($parsed['open']);
                }
            }
        }

        // Check the next 7 days
        for ($i = 1; $i <= 7; $i++) {
            $date = $now->copy()->addDays($i);
            $hours = $this->getHoursForDate($date);

            if ($hours && strtolower($hours) !== 'closed') {
                $parsed = $this->parseHoursString($hours);
                if ($parsed) {
                    return $date->setTimeFromTimeString($parsed['open']);
                }
            }
        }

        return null;
    }

    /**
     * Get available scheduling times for the next N days.
     */
    public function getSchedulingSlots(int $days = 7, int $intervalMinutes = 30): array
    {
        $slots = [];
        $now = now();
        $prepTime = (int) ($this->getOrderingSettings()['estimated_prep_time_minutes'] ?? 30);

        for ($i = 0; $i <= $days; $i++) {
            $date = $now->copy()->addDays($i)->startOfDay();
            $hours = $this->getHoursForDate($date);

            if (!$hours || strtolower($hours) === 'closed') {
                continue;
            }

            $parsed = $this->parseHoursString($hours);
            if (!$parsed) {
                continue;
            }

            $daySlots = [];
            $openTime = $date->copy()->setTimeFromTimeString($parsed['open']);
            $closeTime = $date->copy()->setTimeFromTimeString($parsed['close']);

            // For today, start from now + prep time
            if ($i === 0) {
                $earliestSlot = $now->copy()->addMinutes($prepTime)->ceil($intervalMinutes . ' minutes');
                if ($earliestSlot->gt($openTime)) {
                    $openTime = $earliestSlot;
                }
            }

            // Stop accepting orders 30 minutes before close
            $closeTime->subMinutes(30);

            $slotTime = $openTime->copy();
            while ($slotTime->lte($closeTime)) {
                $daySlots[] = $slotTime->format('g:i A');
                $slotTime->addMinutes($intervalMinutes);
            }

            if (!empty($daySlots)) {
                $slots[] = [
                    'date' => $date->format('Y-m-d'),
                    'label' => $i === 0 ? 'Today' : ($i === 1 ? 'Tomorrow' : $date->format('l, M j')),
                    'times' => $daySlots,
                ];
            }
        }

        return $slots;
    }

    /**
     * Get ordering settings with defaults.
     */
    public function getOrderingSettings(): array
    {
        $defaults = [
            'accepts_delivery' => false,
            'accepts_pickup' => true,
            'minimum_order' => 0,
            'delivery_fee' => 0,
            'delivery_radius_km' => 10,
            'estimated_prep_time_minutes' => 30,
            'notification_email' => $this->email,
            'notification_phone' => $this->phone,
            'auto_confirm' => false,
            'tax_rate' => 0.15,
        ];

        return array_merge($defaults, $this->ordering_settings ?? []);
    }

    /**
     * Check if ordering is enabled and configured.
     */
    public function canAcceptOrders(): bool
    {
        if (!$this->ordering_enabled) {
            return false;
        }

        $settings = $this->getOrderingSettings();
        return $settings['accepts_pickup'] || $settings['accepts_delivery'];
    }

    /**
     * Check if this site can use delivery zones (Max tier).
     */
    public function canUseDeliveryZones(): bool
    {
        return $this->restaurantPlan && $this->restaurantPlan->delivery_zones;
    }

    /**
     * Check if this site can use catering (Pro+ tier).
     */
    public function canUseCatering(): bool
    {
        return $this->restaurantPlan && $this->restaurantPlan->catering;
    }

    /**
     * Get catering settings with defaults.
     */
    public function getCateringSettings(): array
    {
        $defaults = [
            'notification_email' => $this->email,
            'notification_phone' => $this->phone,
            'lead_time_hours' => 48,
            'min_guests' => 10,
            'deposit_required' => false,
            'custom_message' => null,
        ];

        return array_merge($defaults, $this->catering_settings ?? []);
    }

    /**
     * Get catering packages - inherit from parent if child location.
     */
    public function getCateringPackages()
    {
        if ($this->isChildLocation() && $this->parentSite) {
            return $this->parentSite->cateringPackages()->active()->ordered()->get();
        }

        return $this->cateringPackages()->active()->ordered()->get();
    }

    /**
     * Check if this site can accept built-in reservations (Max tier).
     */
    public function canAcceptReservations(): bool
    {
        return $this->restaurantPlan && $this->restaurantPlan->built_in_reservations;
    }

    /**
     * Get reservation settings with defaults.
     */
    public function getReservationSettings(): array
    {
        $defaults = [
            'enabled' => false,
            'type' => 'email',
            'max_covers_per_slot' => 40,
            'slot_duration_minutes' => 30,
            'max_party_size' => 12,
            'default_duration_minutes' => 90,
            'advance_booking_days' => 30,
            'min_advance_hours' => 2,
            'auto_confirm' => false,
            'notification_email' => $this->email,
            'notification_phone' => $this->phone,
            'confirmation_message' => null,
            'cancellation_policy' => null,
            'blocked_dates' => [],
        ];

        return array_merge($defaults, $this->reservation_settings ?? []);
    }

    /**
     * Check if this site is a parent (brand/chain) with child locations.
     */
    public function isParentLocation(): bool
    {
        return $this->childSites()->exists();
    }

    /**
     * Check if this site is a child location.
     */
    public function isChildLocation(): bool
    {
        return $this->parent_site_id !== null;
    }

    /**
     * Get all locations for this brand (including self if parent).
     */
    public function getAllLocations()
    {
        if ($this->isChildLocation()) {
            // Return parent and all siblings
            return static::where('parent_site_id', $this->parent_site_id)
                ->orWhere('id', $this->parent_site_id)
                ->get();
        }

        // Return self and all children
        return static::where('id', $this->id)
            ->orWhere('parent_site_id', $this->id)
            ->get();
    }

    /**
     * Get the parent brand site (or self if no parent).
     */
    public function getBrandSite(): self
    {
        return $this->parentSite ?? $this;
    }

    /**
     * Get menu categories - inherit from parent if child location.
     */
    public function getMenuCategories()
    {
        if ($this->isChildLocation() && $this->parentSite) {
            // Inherit menu from parent
            return $this->parentSite->categories()
                ->where('active', true)
                ->with(['items' => function ($query) {
                    $query->where('active', true)->orderBy('sort_order');
                }])
                ->get();
        }

        return $this->categories()
            ->where('active', true)
            ->with(['items' => function ($query) {
                $query->where('active', true)->orderBy('sort_order');
            }])
            ->get();
    }

    /**
     * Get featured items - inherit from parent if child location.
     */
    public function getFeaturedItems(int $limit = 6)
    {
        if ($this->isChildLocation() && $this->parentSite) {
            return $this->parentSite->featuredItems($limit);
        }

        return $this->featuredItems($limit);
    }

    /**
     * Link this site as a child of another site.
     */
    public function linkToParent(RestaurantSite $parent): void
    {
        // Can't link to self
        if ($parent->id === $this->id) {
            throw new \InvalidArgumentException('Cannot link a site to itself.');
        }

        // Can't link to a child site
        if ($parent->isChildLocation()) {
            throw new \InvalidArgumentException('Cannot link to a child location. Link to the parent instead.');
        }

        // Can't link if this site has children
        if ($this->isParentLocation()) {
            throw new \InvalidArgumentException('Cannot link a site that has child locations.');
        }

        $this->update([
            'parent_site_id' => $parent->id,
            'is_primary_location' => false,
        ]);
    }

    /**
     * Unlink this site from its parent.
     */
    public function unlinkFromParent(): void
    {
        $this->update([
            'parent_site_id' => null,
            'is_primary_location' => true,
        ]);
    }

    /**
     * Get featured menu items across all categories
     */
    public function featuredItems(int $limit = 6)
    {
        return MenuItem::whereHas('category', function ($query) {
            $query->where('restaurant_site_id', $this->id)
                  ->where('active', true);
        })
            ->where('featured', true)
            ->where('active', true)
            ->orderBy('sort_order')
            ->limit($limit)
            ->get();
    }

    /**
     * Get active announcements
     */
    public function activeAnnouncements()
    {
        return $this->announcements()
            ->where('active', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                      ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('ends_at')
                      ->orWhere('ends_at', '>=', now());
            })
            ->get();
    }

    /**
     * Transform to array for site rendering (API output)
     * Matches the format expected by sos-tech.ca templates
     */
    public function toSiteArray(): array
    {
        // Use getMenuCategories which handles parent/child inheritance
        $categories = $this->getMenuCategories();

        // Build menu_categories format (matches config/samples.php structure)
        $menuCategories = [];
        foreach ($categories as $category) {
            $slug = \Str::slug($category->name, '_');
            $items = [];
            foreach ($category->items as $item) {
                $itemData = [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'price' => $item->formatted_price,
                ];
                if ($item->price_note) {
                    $itemData['note'] = $item->price_note;
                }
                if ($item->image_path) {
                    $itemData['image'] = Storage::disk('public')->url($item->image_path);
                }
                if ($item->alt_text) {
                    $itemData['alt_text'] = $item->alt_text;
                }
                if ($item->hasDietaryInfo()) {
                    $itemData['dietary'] = $item->dietary_info;
                    $itemData['dietary_labels'] = $item->getDietaryLabels();
                }
                if ($item->hasBadges()) {
                    $itemData['badges'] = $item->getBadgeData();
                }
                if ($item->featured) {
                    $itemData['featured'] = true;
                }
                $items[] = $itemData;
            }
            $menuCategories[$slug] = [
                'name' => $category->name,
                'description' => $category->description,
                'items' => $items,
            ];
        }

        // Build menu_highlights (featured items) - uses 'desc' not 'description'
        // Uses getFeaturedItems which handles parent/child inheritance
        $featuredItems = $this->getFeaturedItems(6);
        $menuHighlights = $featuredItems->map(function ($item) {
            $highlight = [
                'name' => $item->name,
                'price' => $item->formatted_price,
                'desc' => $item->description ?? '',
            ];
            if ($item->image_path) {
                $highlight['image'] = Storage::disk('public')->url($item->image_path);
            }
            if ($item->alt_text) {
                $highlight['alt_text'] = $item->alt_text;
            }
            return $highlight;
        })->toArray();

        // Get active announcements
        $announcements = $this->activeAnnouncements()->map(function ($announcement) {
            return [
                'message' => $announcement->message,
                'title' => $announcement->title,
                'image' => $announcement->image_url,
                'link_url' => $announcement->link_url,
                'type' => $announcement->type,
                'starts_at' => optional($announcement->starts_at)->toIso8601String(),
                'ends_at' => optional($announcement->ends_at)->toIso8601String(),
            ];
        })->toArray();

        // Get holiday hours for the next 30 days
        $holidayHours = $this->holidayHours()
            ->where('date', '>=', now()->toDateString())
            ->where('date', '<=', now()->addDays(30)->toDateString())
            ->get()
            ->map(function ($holiday) {
                return [
                    'date' => $holiday->date->format('Y-m-d'),
                    'hours' => $holiday->hours,
                    'label' => $holiday->label,
                ];
            })
            ->toArray();

        // Build ordering data for frontend
        $orderingData = null;
        if ($this->ordering_enabled) {
            $settings = $this->getOrderingSettings();
            // Demo sites always appear open so the ordering flow works for prospects
            $isCurrentlyOpen = ($this->status === self::STATUS_DEMO) ? true : $this->isCurrentlyOpen();
            $nextOpenTime = !$isCurrentlyOpen ? $this->getNextOpenTime() : null;

            $orderingData = [
                'enabled' => true,
                'accepts_pickup' => $settings['accepts_pickup'],
                'accepts_delivery' => $settings['accepts_delivery'],
                'minimum_order' => $settings['minimum_order'],
                'delivery_fee' => $settings['delivery_fee'],
                'estimated_prep_time_minutes' => $settings['estimated_prep_time_minutes'],
                'tax_rate' => $settings['tax_rate'],
                'is_open' => $isCurrentlyOpen,
                'today_hours' => $this->getHoursForDate(now()),
                'next_open' => $nextOpenTime?->toIso8601String(),
                'next_open_label' => $nextOpenTime
                    ? ($nextOpenTime->isToday()
                        ? 'Opens at ' . $nextOpenTime->format('g:i A')
                        : $nextOpenTime->format('l \a\t g:i A'))
                    : null,
                'scheduling_slots' => $this->getSchedulingSlots(7, 30),
            ];

            // Include delivery zone data if available
            if ($this->canUseDeliveryZones() && $this->latitude && $this->longitude) {
                $zones = $this->deliveryZones()->active()->ordered()->get();
                if ($zones->isNotEmpty()) {
                    $orderingData['has_delivery_zones'] = true;
                    $orderingData['restaurant_lat'] = (float) $this->latitude;
                    $orderingData['restaurant_lng'] = (float) $this->longitude;
                    $orderingData['delivery_zones'] = $zones->map(function ($zone) {
                        return [
                            'name' => $zone->name,
                            'radius_km' => (float) $zone->radius_km,
                            'delivery_fee' => (float) $zone->delivery_fee,
                            'minimum_order' => (float) $zone->minimum_order,
                            'estimated_delivery_minutes' => $zone->estimated_delivery_minutes,
                        ];
                    })->toArray();
                    $orderingData['max_delivery_radius'] = (float) $zones->max('radius_km');
                }
            }
        }

        // Build reservation data
        $reservationData = null;
        if (!empty($this->reservation_settings['enabled'])) {
            $resSettings = $this->getReservationSettings();
            $reservationData = [
                'enabled' => true,
                'type' => $resSettings['type'],
                'opentable_id' => $this->reservation_settings['opentable_id'] ?? null,
                'resy_venue_id' => $this->reservation_settings['resy_venue_id'] ?? null,
                'email' => $resSettings['notification_email'] ?? $this->email,
                'custom_embed' => $this->reservation_settings['custom_embed'] ?? null,
            ];

            // Include built-in reservation config
            if ($resSettings['type'] === 'built_in' && $this->canAcceptReservations()) {
                $reservationData['max_party_size'] = $resSettings['max_party_size'];
                $reservationData['advance_booking_days'] = $resSettings['advance_booking_days'];
                $reservationData['min_advance_hours'] = $resSettings['min_advance_hours'];
                $reservationData['slot_duration_minutes'] = $resSettings['slot_duration_minutes'];
                $reservationData['confirmation_message'] = $resSettings['confirmation_message'];
                $reservationData['cancellation_policy'] = $resSettings['cancellation_policy'];
            }
        }

        // Build locations data for multi-location support
        $locationsData = null;
        if ($this->isParentLocation() || $this->isChildLocation()) {
            $allLocations = $this->getAllLocations();
            $locationsData = $allLocations->map(function ($location) {
                return [
                    'id' => $location->id,
                    'slug' => $location->slug,
                    'name' => $location->business_name,
                    'address' => $location->address,
                    'phone' => $location->phone,
                    'is_primary' => $location->is_primary_location,
                    'url' => $location->getPublicUrl(),
                ];
            })->toArray();
        }

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'status' => $this->status,
            'name' => $this->business_name,
            'tagline' => $this->tagline,
            'seo_title' => $this->seo_title,
            'seo_description' => $this->seo_description,
            'seo_keywords' => $this->seo_keywords,
            'cuisine_type' => $this->cuisine_type,
            'price_range' => $this->price_range,
            'template' => $this->settings['template'] ?? 'restaurant',
            'logo' => $this->logo_url,
            'cover_photo' => $this->cover_photo_url,
            'og_image' => $this->og_image_path ? Storage::disk('public')->url($this->og_image_path) : null,
            'phone' => $this->phone,
            'email' => $this->email,
            'address' => $this->attributes['address'] ?? '',
            'address_street' => explode(',', $this->attributes['address'] ?? '')[0] ?? '',
            'address_city' => trim(explode(',', $this->attributes['address'] ?? '')[1] ?? ''),
            'address_province' => trim(explode(',', $this->attributes['address'] ?? '')[2] ?? 'NB'),
            'address_postal' => trim(explode(',', $this->attributes['address'] ?? '')[3] ?? ''),
            'address_country' => 'CA',
            'timezone' => $this->getTimezone(),
            'force_closed' => (bool) $this->force_closed,
            'closure_message' => $this->closure_message,
            'hours' => $this->getHours(),
            'holiday_hours' => $holidayHours,
            'colors' => $this->getColors(),
            'social_proof' => $this->social_proof ?? [],
            'social_links' => $this->settings['social_links'] ?? [],
            'settings' => $this->settings ?? [],
            'menu_categories' => $menuCategories,
            'menu_highlights' => $menuHighlights,
            'announcements' => $announcements,
            'features' => $this->settings['features'] ?? [],
            'testimonials' => $this->settings['testimonials'] ?? [],
            'sister_sites' => $this->getSisterSitesData(),
            'cta_text' => $this->settings['cta_text'] ?? 'Call Us',
            'cta_url' => $this->settings['cta_url'] ?? ('tel:' . preg_replace('/[^0-9]/', '', $this->phone ?? '')),
            'secondary_cta_text' => $this->settings['secondary_cta_text'] ?? 'Get Directions',
            'secondary_cta_url' => $this->settings['secondary_cta_url'] ?? ('https://maps.google.com/?q=' . urlencode($this->attributes['address'] ?? '')),
            'gallery' => collect($this->settings['gallery'] ?? [])->map(function ($entry) {
                // Backward compat: legacy entries are bare path strings; new entries are
                // ['path' => ..., 'caption' => ...]. Always emit ['url', 'caption'] for templates.
                if (is_array($entry)) {
                    return [
                        'url' => Storage::disk('public')->url($entry['path'] ?? ''),
                        'caption' => $entry['caption'] ?? null,
                    ];
                }
                return ['url' => Storage::disk('public')->url($entry), 'caption' => null];
            })->toArray(),
            'ordering' => $orderingData,
            'reservations' => $reservationData,
            'catering' => $this->buildCateringArray(),
            'locations' => $locationsData,
            'is_primary_location' => $this->is_primary_location,
            'parent_site_id' => $this->parent_site_id,
            // Hero customization
            'hero_image' => !empty($this->settings['hero_image'])
                ? Storage::disk('public')->url($this->settings['hero_image'])
                : null,
            'hero_video' => !empty($this->settings['hero_video'])
                ? Storage::disk('public')->url($this->settings['hero_video'])
                : null,
            'hero_poster' => !empty($this->settings['hero_poster'])
                ? Storage::disk('public')->url($this->settings['hero_poster'])
                : null,
            // Google Reviews integration
            'google_place_id' => $this->google_place_id ?? $this->settings['google_place_id'] ?? null,
            'google_rating' => $this->settings['google_rating'] ?? null,
            'google_review_count' => $this->settings['google_review_count'] ?? null,
            'google_reviews' => $this->settings['google_reviews'] ?? [],
            'google_reviews_url' => $this->settings['google_reviews_url'] ?? null,
        ];
    }

    /**
     * Build catering data for site array output.
     */
    protected function buildCateringArray(): ?array
    {
        if (!$this->catering_enabled || !$this->canUseCatering()) {
            return null;
        }

        $settings = $this->getCateringSettings();
        $packages = $this->getCateringPackages();

        return [
            'enabled' => true,
            'lead_time_hours' => $settings['lead_time_hours'],
            'min_guests' => $settings['min_guests'],
            'custom_message' => $settings['custom_message'],
            'packages' => $packages->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'description' => $package->description,
                    'price' => $package->formatted_price,
                    'price_raw' => (float) $package->price,
                    'price_type' => $package->price_type,
                    'min_guests' => $package->min_guests,
                    'max_guests' => $package->max_guests,
                    'lead_time_hours' => $package->lead_time_hours,
                    'includes' => $package->includes ?? [],
                    'image' => $package->image_url,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get the storage path for this site's files
     */
    public function getStoragePath(): string
    {
        return "restaurants/{$this->client_id}/{$this->id}";
    }

    public function customDomains(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RestaurantCustomDomain::class);
    }

    public function primaryCustomDomain(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(RestaurantCustomDomain::class)->where('is_primary', true);
    }

    /**
     * Parse the flat address string into structured components for schema.org
     */
    public function parseAddress(): array
    {
        $raw = $this->address ?? '';
        $parts = array_map('trim', explode(',', $raw));
        
        $street = $parts[0] ?? '';
        $city = $parts[1] ?? '';
        $province = $parts[2] ?? 'NB';
        $postal = $parts[3] ?? '';
        
        return [
            'full' => $raw,
            'street' => $street,
            'city' => $city,
            'province' => $province,
            'postal' => $postal,
            'country' => 'CA',
        ];
    }
}
