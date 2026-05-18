<?php

namespace App\Policies;

use App\Models\RestaurantSite;
use App\Models\User;

class RestaurantSitePolicy
{
    /**
     * Determine if the user can manage the given restaurant site.
     *
     * Owners can manage their own sites. Admins can manage any site.
     * (Admin manage-anything replaces the portal-era "impersonate client"
     * workflow — Frank navigates directly to /client/restaurant/{id} for
     * any restaurant and gets full owner-portal access.)
     */
    public function manage(User $user, RestaurantSite $site): bool
    {
        if ($user->is_admin) {
            return true;
        }

        return $site->client_id === $user->id;
    }
}
