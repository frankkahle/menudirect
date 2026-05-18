<?php

namespace App\Models;

use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantCustomDomain extends RestaurantModel
{

    protected $fillable = [
        'restaurant_site_id',
        'domain',
        'cloudflare_zone_id',
        'dns_configured',
        'is_primary',
        'status',
        'notes',
    ];

    protected $casts = [
        'dns_configured' => 'boolean',
        'is_primary' => 'boolean',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_ACTIVE = 'active';
    const STATUS_FAILED = 'failed';

    public function restaurantSite(): BelongsTo
    {
        return $this->belongsTo(RestaurantSite::class);
    }
}
