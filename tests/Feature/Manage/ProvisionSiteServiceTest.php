<?php

namespace Tests\Feature\Manage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\SiteProvisioningService;
use App\Models\{User, RestaurantSite, RestaurantPlan};

class ProvisionSiteServiceTest extends TestCase
{
    use RefreshDatabase;

    private function plan(array $o = []): RestaurantPlan
    {
        return RestaurantPlan::create(array_merge([
            'name' => 'SiteFresh', 'slug' => 'sitefresh', 'price_monthly' => 35, 'price_annual' => 350,
            'online_ordering' => true,
        ], $o));
    }

    public function test_provisions_site_with_plan_and_synced_flags(): void
    {
        $owner = User::create(['name' => 'O', 'email' => 'o@x.com', 'password' => bcrypt('x')]);
        $plan = $this->plan();
        $svc = app(SiteProvisioningService::class);

        $site = $svc->provisionSite([
            'business_name' => "Buster's Burgers", 'plan_id' => $plan->id, 'owner_id' => $owner->id,
        ]);

        $this->assertEquals('busters-burgers', $site->slug);
        $this->assertEquals($plan->id, $site->restaurant_plan_id);
        $this->assertEquals(RestaurantSite::PLAN_SELFSERVICE, $site->plan); // sitefresh -> selfservice
        $this->assertEquals(RestaurantSite::STATUS_ACTIVE, $site->status);
        $this->assertTrue((bool) $site->ordering_enabled);
    }

    public function test_duplicate_slug_throws_conflict(): void
    {
        $owner = User::create(['name' => 'O', 'email' => 'o2@x.com', 'password' => bcrypt('x')]);
        $plan = $this->plan(['slug' => 'basic', 'online_ordering' => false]);
        $svc = app(SiteProvisioningService::class);
        $svc->provisionSite(['business_name' => 'Dup', 'slug' => 'dup', 'plan_id' => $plan->id, 'owner_id' => $owner->id]);

        $this->expectException(\App\Exceptions\ProvisioningConflictException::class);
        $svc->provisionSite(['business_name' => 'Dup2', 'slug' => 'dup', 'plan_id' => $plan->id, 'owner_id' => $owner->id]);
    }

    public function test_resolves_owner_by_email(): void
    {
        $owner = User::create(['name' => 'O', 'email' => 'byemail@x.com', 'password' => bcrypt('x')]);
        $plan = $this->plan(['slug' => 'basic', 'online_ordering' => false]);
        $svc = app(SiteProvisioningService::class);

        $site = $svc->provisionSite(['business_name' => 'ByEmail', 'plan_id' => $plan->id, 'owner_email' => 'byemail@x.com']);
        $this->assertEquals($owner->id, $site->client_id);
    }
}
