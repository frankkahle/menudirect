<?php

namespace App\Models;

use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpTourCompletion extends RestaurantModel
{

    protected $fillable = [
        'tour_id',
        'client_id',
        'restaurant_site_id',
        'completed_at',
        'dismissed_at',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'dismissed_at' => 'datetime',
    ];

    public function tour(): BelongsTo
    {
        return $this->belongsTo(HelpTour::class, 'tour_id');
    }
}
