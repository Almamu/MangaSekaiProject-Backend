<?php

namespace Tests\Feature;

use App\Jobs\ScanMedia;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class JobQueueTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_job_queue(): void
    {
        Queue::fake([
            ScanMedia::class,
        ]);

        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $token = $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->hasAll(['token', 'token_type', 'expires_in']))
            ->json('token');

        $response = $this->post('/api/v1/admin/media/refresh', ['Authorization' => 'Bearer '.$token]);

        $response
            ->assertStatus(200);

        Queue::assertPushed(ScanMedia::class);
    }
}
