<?php

namespace App\Models;

use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpTour extends RestaurantModel
{

    protected $fillable = [
        'slug',
        'title',
        'description',
        'category',
        'trigger_type',
        'trigger_path',
        'steps',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'steps' => 'array',
        'is_published' => 'boolean',
    ];

    public function completions(): HasMany
    {
        return $this->hasMany(HelpTourCompletion::class, 'tour_id');
    }

    public function scopePublished($q)
    {
        return $q->where('is_published', true);
    }

    public function isCompletedBy(int $clientId, ?int $siteId = null): bool
    {
        return $this->completions()
            ->where('client_id', $clientId)
            ->where('restaurant_site_id', $siteId)
            ->whereNotNull('completed_at')
            ->exists();
    }

    public function isDismissedBy(int $clientId, ?int $siteId = null): bool
    {
        return $this->completions()
            ->where('client_id', $clientId)
            ->where('restaurant_site_id', $siteId)
            ->whereNotNull('dismissed_at')
            ->exists();
    }
}
