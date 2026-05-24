<?php

namespace Tests\Feature\Manage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private array $hdr = ['Authorization' => 'Bearer test-management-token'];

    public function test_missing_token_is_401(): void
    {
        $this->postJson('/api/v1/manage/ping', [])
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'unauthorized');
    }

    public function test_bad_token_is_401(): void
    {
        $this->withHeaders(['Authorization' => 'Bearer wrong'])
            ->postJson('/api/v1/manage/ping', [])
            ->assertStatus(401);
    }

    public function test_disallowed_ip_is_403(): void
    {
        $this->withHeaders($this->hdr)
            ->withServerVariables(['REMOTE_ADDR' => '10.9.9.9'])
            ->postJson('/api/v1/manage/ping', [])
            ->assertStatus(403)
            ->assertJsonPath('error.code', 'ip_forbidden');
    }

    public function test_valid_token_and_ip_passes(): void
    {
        $this->withHeaders($this->hdr)
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/api/v1/manage/ping', [])
            ->assertOk()
            ->assertJsonPath('ok', true);
    }
}
