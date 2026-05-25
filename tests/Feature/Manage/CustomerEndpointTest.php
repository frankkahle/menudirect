<?php

namespace Tests\Feature\Manage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\{RestaurantPlan, User, RestaurantSite};

class CustomerEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function hdr()
    {
        return $this->withHeaders(['Authorization' => 'Bearer test-management-token'])
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1']);
    }

    public function test_creates_owner_and_site_atomically(): void
    {
        $plan = RestaurantPlan::create(['name' => 'Basic', 'slug' => 'basic', 'price_monthly' => 15, 'price_annual' => 150]);
        $this->hdr()->postJson('/api/v1/manage/customers', [
            'owner' => ['email' => 'new@x.com', 'name' => 'New Owner'],
            'site' => ['business_name' => 'New Resto', 'slug' => 'new-resto'],
            'plan_id' => $plan->id,
        ])->assertStatus(201)
          ->assertJsonPath('site.slug', 'new-resto')
          ->assertJsonPath('owner.email', 'new@x.com')
          ->assertJsonStructure(['owner' => ['id'], 'set_password_url', 'site' => ['id']]);

        $this->assertDatabaseHas('users', ['email' => 'new@x.com']);
        $this->assertDatabaseHas('restaurant_sites', ['slug' => 'new-resto']);
    }

    public function test_duplicate_slug_rolls_back_owner(): void
    {
        $plan = RestaurantPlan::create(['name' => 'Basic', 'slug' => 'basic', 'price_monthly' => 15, 'price_annual' => 150]);
        $existingOwner = User::create(['name' => 'X', 'email' => 'pre@x.com', 'password' => bcrypt('x')]);
        RestaurantSite::create(['client_id' => $existingOwner->id, 'restaurant_plan_id' => $plan->id, 'slug' => 'taken', 'business_name' => 'T', 'status' => 'active', 'plan' => 'basic']);

        $this->hdr()->postJson('/api/v1/manage/customers', [
            'owner' => ['email' => 'rollback@x.com', 'name' => 'RB'],
            'site' => ['business_name' => 'RB', 'slug' => 'taken'],
            'plan_id' => $plan->id,
        ])->assertStatus(409);

        $this->assertDatabaseMissing('users', ['email' => 'rollback@x.com']);
    }
}
