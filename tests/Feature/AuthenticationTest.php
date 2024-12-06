<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_unauthenticated(): void
    {
        // try to read anything without authentication first
        $response = $this->get('/api/v1/series');

        $response->assertStatus(401);
    }

    public function test_authentication(): void
    {
        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $token = $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->hasAll(['token', 'token_type', 'expires_in']))
            ->json('token');

        // refresh current token
        $response = $this->post('/api/v1/auth/refresh', headers: ['Authorization' => 'Bearer '.$token]);

        $token = $response
            ->assertStatus(200)
            ->assertJson(fn (AssertableJson $json) => $json->hasAll(['token', 'token_type', 'expires_in']))
            ->json('token');

        // finally logout
        $response = $this->post('/api/v1/auth/logout', headers: ['Authorization' => 'Bearer '.$token]);

        $response->assertStatus(200);
    }

    public function test_wrong_authentication_and_ratelimit(): void
    {
        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(401)->assertJson(['message' => 'INVALID_CREDENTIALS']);

        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(401)->assertJson(['message' => 'INVALID_CREDENTIALS']);

        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(401)->assertJson(['message' => 'INVALID_CREDENTIALS']);

        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(401)->assertJson(['message' => 'INVALID_CREDENTIALS']);

        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(401)->assertJson(['message' => 'INVALID_CREDENTIALS']);

        $response = $this->post('/api/v1/auth/login', [
            'username' => 'admin',
            'password' => 'admin',
        ]);

        $response->assertStatus(401)->assertJson(['message' => 'TOO_MANY_ATTEMPTS']);
    }
}
