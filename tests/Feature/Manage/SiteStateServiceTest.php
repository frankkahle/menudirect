<?php

namespace Tests\Feature\Manage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\SiteProvisioningService;
use App\Models\{User, RestaurantSite, RestaurantPlan};

class SiteStateServiceTest extends TestCase
{
    use RefreshDatabase;

    private SiteProvisioningService $svc;
    private RestaurantSite $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = app(SiteProvisioningService::class);
        $owner = User::create(['name' => 'O', 'email' => 'o@x.com', 'password' => bcrypt('x')]);
        $basic = RestaurantPlan::create(['name' => 'Basic', 'slug' => 'basic', 'price_monthly' => 15, 'price_annual' => 150, 'online_ordering' => false]);
        $this->site = $this->svc->provisionSite(['business_name' => 'S', 'plan_id' => $basic->id, 'owner_id' => $owner->id]);
    }

    public function test_change_plan_updates_ids_and_flags(): void
    {
        $pro = RestaurantPlan::create(['name' => 'Pro', 'slug' => 'sitefresh-pro', 'price_monthly' => 59, 'price_annual' => 590, 'online_ordering' => true]);
        $site = $this->svc->changePlan($this->site, $pro->id);
        $this->assertEquals($pro->id, $site->restaurant_plan_id);
        $this->assertEquals(RestaurantSite::PLAN_PREMIUM, $site->plan);
        $this->assertTrue((bool) $site->ordering_enabled);
    }

    public function test_set_status_suspended(): void
    {
        $site = $this->svc->setStatus($this->site, 'suspended');
        $this->assertEquals(RestaurantSite::STATUS_SUSPENDED, $site->status);
        $this->assertNull($site->archived_at);
    }

    public function test_set_status_archived_sets_timestamp(): void
    {
        $this->svc->setStatus($this->site, 'archived');
        $reloaded = RestaurantSite::withoutGlobalScope('notArchived')->find($this->site->id);
        $this->assertNotNull($reloaded->archived_at);
    }
}
