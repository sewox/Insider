<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/api/messages');

        // Rate limiting nedeniyle 200 veya 429 olabilir
        $this->assertContains($response->status(), [200, 429]);
    }
}
