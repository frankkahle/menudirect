<?php

namespace Tests\Feature\Manage;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;

class IdempotencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_key_does_not_create_twice(): void
    {
        $h = ['Authorization' => 'Bearer test-management-token', 'Idempotency-Key' => 'abc-123'];
        $body = ['email' => 'idem@x.com', 'name' => 'Idem'];

        $r1 = $this->withHeaders($h)->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/api/v1/manage/owners', $body)->assertStatus(201);
        $r2 = $this->withHeaders($h)->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->postJson('/api/v1/manage/owners', $body)->assertStatus(201);

        $this->assertEquals($r1->json('owner.id'), $r2->json('owner.id'));
        $this->assertSame(1, User::where('email', 'idem@x.com')->count());
    }
}
