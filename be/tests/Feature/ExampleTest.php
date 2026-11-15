<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_backend_gateway_root_returns_service_info(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200)
            ->assertJsonPath('status', 'running');
    }

    public function test_unauthenticated_api_dashboard_returns_unauthorized(): void
    {
        $response = $this->getJson('/api/dashboard');

        $response->assertStatus(401);
    }
}
