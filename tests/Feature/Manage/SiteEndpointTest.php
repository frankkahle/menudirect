<?php

namespace Tests\Feature\Manage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\{User, RestaurantSite, RestaurantPlan};

class SiteEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function hdr()
    {
        return $this->withHeaders(['Authorization' => 'Bearer test-management-token'])
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);
    }

    private function seedData(): array
    {
        $owner = User::create(['name' => 'O', 'email' => 'o@x.com', 'password' => bcrypt('x')]);
        $plan = RestaurantPlan::create(['name' => 'Basic', 'slug' => 'basic', 'price_monthly' => 15, 'price_annual' => 150, 'online_ordering' => false]);
        return [$owner, $plan];
    }

    public function test_provision_site_201(): void
    {
        [$owner, $plan] = $this->seedData();
        $this->hdr()->postJson('/api/v1/manage/sites', [
            'business_name' => 'Busters', 'plan_id' => $plan->id, 'owner_id' => $owner->id,
        ])->assertStatus(201)->assertJsonPath('site.slug', 'busters')->assertJsonPath('site.status', 'active');
    }

    public function test_duplicate_slug_409(): void
    {
        [$owner, $plan] = $this->seedData();
        $body = ['business_name' => 'Dup', 'slug' => 'dup', 'plan_id' => $plan->id, 'owner_id' => $owner->id];
        $this->hdr()->postJson('/api/v1/manage/sites', $body)->assertStatus(201);
        $this->hdr()->postJson('/api/v1/manage/sites', $body)->assertStatus(409)->assertJsonPath('error.code', 'conflict');
    }

    public function test_change_plan_and_status(): void
    {
        [$owner, $plan] = $this->seedData();
        $site = RestaurantSite::create(['client_id' => $owner->id, 'restaurant_plan_id' => $plan->id, 'slug' => 's1', 'business_name' => 'S1', 'status' => 'active', 'plan' => 'basic']);
        $pro = RestaurantPlan::create(['name' => 'Pro', 'slug' => 'sitefresh-pro', 'price_monthly' => 59, 'price_annual' => 590, 'online_ordering' => true]);

        $this->hdr()->patchJson("/api/v1/manage/sites/{$site->id}/plan", ['plan_id' => $pro->id])
            ->assertOk()->assertJsonPath('site.plan', 'premium');
        $this->hdr()->patchJson("/api/v1/manage/sites/{$site->id}/status", ['status' => 'suspended'])
            ->assertOk()->assertJsonPath('site.status', 'suspended');
    }

    public function test_unknown_site_is_404(): void
    {
        $this->seedData();
        $this->hdr()->patchJson('/api/v1/manage/sites/999999/status', ['status' => 'active'])
            ->assertStatus(404)->assertJsonPath('error.code', 'not_found');
    }
}
