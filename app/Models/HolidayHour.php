<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HolidayHour extends RestaurantModel
{
    use HasFactory;


    protected $fillable = [
        'restaurant_site_id',
        'date',
        'hours',
        'label',
    ];

    protected $casts = [
        'date' => 'date',
    ];

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
    public function scopeForDate($query, $date)
    {
        return $query->where('date', $date);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('date', '>=', now()->toDateString())->orderBy('date');
    }

    public function scopePast($query)
    {
        return $query->where('date', '<', now()->toDateString())->orderByDesc('date');
    }

    /**
     * Check if this holiday is for a closed day
     */
    public function isClosed(): bool
    {
        return strtolower($this->hours) === 'closed';
    }

    /**
     * Get the formatted date for display
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->date->format('F j, Y');
    }

    /**
     * Get display label (uses label if set, otherwise formatted date)
     */
    public function getDisplayLabelAttribute(): string
    {
        return $this->label ?? $this->formatted_date;
    }
}
