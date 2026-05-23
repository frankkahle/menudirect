<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(["name", "email", "password"])]
#[Hidden(["password", "remember_token"])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            "email_verified_at" => "datetime",
            "password" => "hashed",
            "is_admin" => "boolean",
        ];
    }

    /**
     * Restaurants this user co-manages via the restaurant_site_user pivot.
     */
    public function managedRestaurants(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(RestaurantSite::class, "restaurant_site_user")
            ->withPivot("role")
            ->withTimestamps();
    }

    /**
     * Restaurants this user owns (legacy client_id) — kept for backwards compat.
     */
    public function ownedRestaurants(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RestaurantSite::class, "client_id");
    }

    /**
     * Demo sandbox flag. No live demo flow on this VM yet — wire to demo_sessions
     * (client_id FK) if/when anonymous demo accounts get user records.
     */
    public function isDemoAccount(): bool
    {
        return false;
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }
}
