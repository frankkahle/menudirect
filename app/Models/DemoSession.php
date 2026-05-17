<?php

namespace App\Models;

use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DemoSession extends RestaurantModel
{

    protected $fillable = [
        'token',
        'client_id',
        'restaurant_site_id',
        'email',
        'name',
        'ip_address',
        'expires_at',
        'last_activity_at',
        'cleaned_up_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'cleaned_up_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($session) {
            if (empty($session->token)) {
                $session->token = Str::random(64);
            }
        });
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function restaurantSite(): BelongsTo
    {
        return $this->belongsTo(RestaurantSite::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return !$this->isExpired() && is_null($this->cleaned_up_at);
    }

    public function touchActivity(): void
    {
        $this->update(['last_activity_at' => now()]);
    }

    public function getRemainingMinutes(): int
    {
        return max(0, (int) now()->diffInMinutes($this->expires_at, false));
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now())->whereNull('cleaned_up_at');
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    public function scopeNeedsCleanup($query)
    {
        $cleanupAfter = config('demo.cleanup_after_hours', 24);

        return $query->where('expires_at', '<=', now()->subHours($cleanupAfter))
            ->whereNull('cleaned_up_at');
    }
}
