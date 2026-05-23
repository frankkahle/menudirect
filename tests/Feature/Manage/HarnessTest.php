<?php

namespace Tests\Feature\Manage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\RestaurantSite;

class HarnessTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_persist_on_the_menudirect_connection(): void
    {
        $user = User::create(['name' => 'T', 'email' => 't@example.com', 'password' => bcrypt('x')]);
        $site = RestaurantSite::create([
            'client_id' => $user->id,
            'slug' => 'harness-test',
            'business_name' => 'Harness',
            'status' => RestaurantSite::STATUS_ACTIVE,
            'plan' => RestaurantSite::PLAN_BASIC,
        ]);

        $this->assertDatabaseHas('restaurant_sites', ['slug' => 'harness-test']);
        $this->assertEquals($user->id, $site->client_id);
    }
}
