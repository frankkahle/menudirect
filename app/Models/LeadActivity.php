<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadActivity extends RestaurantModel
{
    use HasFactory;


    const TYPE_NOTE = 'note';
    const TYPE_CALL = 'call';
    const TYPE_EMAIL = 'email';
    const TYPE_SMS = 'sms';
    const TYPE_VISIT = 'visit';
    const TYPE_STATUS_CHANGE = 'status_change';
    const TYPE_IMPORT = 'import';

    const TYPES = [
        self::TYPE_NOTE => 'Note',
        self::TYPE_CALL => 'Phone Call',
        self::TYPE_EMAIL => 'Email',
        self::TYPE_SMS => 'SMS',
        self::TYPE_VISIT => 'Visit',
        self::TYPE_STATUS_CHANGE => 'Status Change',
        self::TYPE_IMPORT => 'Import',
    ];

    protected $fillable = [
        'restaurant_lead_id',
        'type',
        'description',
        'metadata',
        'performed_by',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // ── Relationships ──

    public function lead(): BelongsTo
    {
        return $this->belongsTo(RestaurantLead::class, 'restaurant_lead_id');
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    // ── Accessors ──

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? ucfirst($this->type);
    }

    public function getTypeIconAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_NOTE => 'M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z',
            self::TYPE_CALL => 'M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z',
            self::TYPE_EMAIL => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
            self::TYPE_SMS => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
            self::TYPE_VISIT => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z',
            self::TYPE_STATUS_CHANGE => 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
            self::TYPE_IMPORT => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4',
            default => 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        };
    }
}
