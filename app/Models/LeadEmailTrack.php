<?php

namespace App\Models;

use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LeadEmailTrack extends RestaurantModel
{

    protected $fillable = [
        'restaurant_lead_id',
        'restaurant_site_id',
        'token',
        'email_type',
        'recipient_email',
        'sent_at',
        'first_opened_at',
        'last_opened_at',
        'open_count',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'first_opened_at' => 'datetime',
        'last_opened_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (LeadEmailTrack $track) {
            if (empty($track->token)) {
                $track->token = Str::random(64);
            }
        });
    }

    // ── Relationships ──

    public function lead(): BelongsTo
    {
        return $this->belongsTo(RestaurantLead::class, 'restaurant_lead_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(RestaurantSite::class, 'restaurant_site_id');
    }

    // ── Helpers ──

    public function recordOpen(string $ip, string $userAgent): void
    {
        $this->update([
            'first_opened_at' => $this->first_opened_at ?? now(),
            'last_opened_at' => now(),
            'open_count' => $this->open_count + 1,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    public function isOpened(): bool
    {
        return $this->first_opened_at !== null;
    }

    // ── Accessors ──

    public function getPixelUrlAttribute(): string
    {
        return url("/t/{$this->token}/px.gif");
    }
}
