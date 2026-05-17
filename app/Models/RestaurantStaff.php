<?php

namespace App\Models;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\RestaurantModel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class RestaurantStaff extends RestaurantModel implements AuthenticatableContract, CanResetPasswordContract
{

    use HasFactory, Authenticatable, CanResetPassword, Notifiable;

    const ROLE_MANAGER = 'manager';
    const ROLE_STAFF = 'staff';
    const ROLE_CONTENT = 'content';

    const ROLES = [
        self::ROLE_MANAGER => 'Manager',
        self::ROLE_STAFF => 'Staff',
        self::ROLE_CONTENT => 'Content (Menu & Social)',
    ];

    protected $table = 'restaurant_staff';

    protected $fillable = [
        'restaurant_site_id',
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'invite_token',
        'invite_sent_at',
        'invite_accepted_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'invite_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'invite_sent_at' => 'datetime',
        'invite_accepted_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
    ];

    public function site(): BelongsTo
    {
        return $this->belongsTo(RestaurantSite::class, 'restaurant_site_id');
    }

    /**
     * Generate a fresh invite token.
     */
    public function generateInviteToken(): string
    {
        $token = Str::random(64);
        $this->update([
            'invite_token' => $token,
            'invite_sent_at' => now(),
        ]);
        return $token;
    }

    /**
     * Check if invite is still valid (not accepted, sent within 7 days).
     */
    public function isInvitePending(): bool
    {
        if ($this->invite_accepted_at) {
            return false;
        }
        if (!$this->invite_sent_at) {
            return false;
        }
        return $this->invite_sent_at->gt(now()->subDays(7));
    }

    /**
     * Role helpers — manager can do everything staff can, plus menu/settings.
     */
    public function isManager(): bool
    {
        return $this->role === self::ROLE_MANAGER;
    }

    public function isContentEditor(): bool
    {
        return $this->role === self::ROLE_CONTENT;
    }

    public function canManageOrders(): bool
    {
        // Content-only staff cannot manage orders
        return $this->is_active && !$this->isContentEditor();
    }

    public function canManageReservations(): bool
    {
        // Content-only staff cannot manage reservations
        return $this->is_active && !$this->isContentEditor();
    }

    public function canManageMenu(): bool
    {
        // Managers and content editors can edit menu/photos
        return $this->is_active && ($this->isManager() || $this->isContentEditor());
    }

    public function canManageAnnouncements(): bool
    {
        // Managers and content editors can post announcements
        return $this->is_active && ($this->isManager() || $this->isContentEditor());
    }

    /**
     * Stub for DemoGuardMiddleware compatibility if staff ever reaches client routes.
     */
    public function isDemoAccount(): bool
    {
        return false;
    }

    public function isAdmin(): bool
    {
        return false;
    }

    public function getAuthIdentifierName()
    {
        return 'id';
    }
}
