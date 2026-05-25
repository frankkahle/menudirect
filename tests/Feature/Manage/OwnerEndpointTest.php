<?php

namespace Tests\Feature\Manage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerEndpointTest extends TestCase
{
    use RefreshDatabase;

    private function submit(array $body)
    {
        return $this->withHeaders(['Authorization' => 'Bearer test-management-token'])
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/api/v1/manage/owners', $body);
    }

    public function test_creates_owner_201_with_invite_url(): void
    {
        $this->submit(['email' => 'o@example.com', 'name' => 'Owner One'])
            ->assertStatus(201)
            ->assertJsonPath('owner.email', 'o@example.com')
            ->assertJsonStructure(['owner' => ['id', 'email', 'name'], 'set_password_url', 'already_existed']);
    }

    public function test_duplicate_email_returns_200_existing(): void
    {
        $this->submit(['email' => 'dup@example.com', 'name' => 'A'])->assertStatus(201);
        $this->submit(['email' => 'dup@example.com', 'name' => 'B'])
            ->assertStatus(200)->assertJsonPath('already_existed', true);
    }

    public function test_invalid_email_is_422(): void
    {
        $this->submit(['email' => 'not-an-email', 'name' => 'X'])
            ->assertStatus(422)->assertJsonPath('error.code', 'validation_failed');
    }
}
