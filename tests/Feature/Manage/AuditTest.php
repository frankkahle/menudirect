<?php

namespace Tests\Feature\Manage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_creation_is_audited(): void
    {
        $this->withHeaders(['Authorization' => 'Bearer test-management-token'])
            ->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/api/v1/manage/owners', ['email' => 'a@x.com', 'name' => 'A'])
            ->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', ['action' => 'manage.owner.created']);
    }
}
