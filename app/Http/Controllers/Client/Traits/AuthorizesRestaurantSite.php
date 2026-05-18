<?php

namespace App\Http\Controllers\Client\Traits;

use App\Models\RestaurantSite;
use Illuminate\Support\Facades\Gate;

trait AuthorizesRestaurantSite
{
    protected function authorizeSite(RestaurantSite $site): void
    {
        Gate::authorize('manage', $site);
    }
}
