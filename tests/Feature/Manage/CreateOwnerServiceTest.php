<?php

namespace Tests\Feature\Manage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\SiteProvisioningService;
use App\Models\User;

class CreateOwnerServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_owner_with_unusable_password_and_invite_url(): void
    {
        $svc = app(SiteProvisioningService::class);
        $r = $svc->createOwner('owner@example.com', 'Jane Doe');

        $this->assertInstanceOf(User::class, $r['owner']);
        $this->assertFalse($r['already_existed']);
        $this->assertStringContainsString('/reset-password/', $r['set_password_url']);
        $this->assertStringContainsString('owner%40example.com', $r['set_password_url']);
        $this->assertDatabaseHas('users', ['email' => 'owner@example.com', 'is_admin' => false]);
    }

    public function test_is_idempotent_on_email(): void
    {
        $svc = app(SiteProvisioningService::class);
        $svc->createOwner('dup@example.com', 'First');
        $r = $svc->createOwner('dup@example.com', 'Second');

        $this->assertTrue($r['already_existed']);
        $this->assertSame(1, User::where('email', 'dup@example.com')->count());
    }
}
