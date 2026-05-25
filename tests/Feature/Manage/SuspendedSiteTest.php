<?php

namespace Tests\Feature\Manage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use App\Models\{User, RestaurantSite, RestaurantPlan};

class SuspendedSiteTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_site_returns_503_holding_page(): void
    {
        Http::fake(); // defensive: never let the self-call hit a real host

        $owner = User::create(['name' => 'O', 'email' => 'o@x.com', 'password' => bcrypt('x')]);
        $plan = RestaurantPlan::create(['name' => 'Basic', 'slug' => 'basic', 'price_monthly' => 15, 'price_annual' => 150]);
        RestaurantSite::create([
            'client_id' => $owner->id, 'restaurant_plan_id' => $plan->id, 'slug' => 'suspendedco',
            'business_name' => 'Suspended Co', 'status' => 'suspended', 'plan' => 'basic',
        ]);

        $this->get('http://suspendedco.menudirect.ca/')
            ->assertStatus(503)
            ->assertSee('temporarily unavailable', false);
    }
}
