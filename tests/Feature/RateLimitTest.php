<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class RateLimitTest extends TestCase
{
    

    /**
     * Test message creation rate limit
     */
    public function test_message_creation_rate_limit(): void
    {
        // Clear any existing rate limit
        $key = sha1('message.create|127.0.0.1');
        RateLimiter::clear($key);
        
        // Make 10 requests (should all pass)
        for ($i = 0; $i < 10; $i++) {
            $response = $this->postJson('/api/messages', [
                'content' => 'Test message ' . $i,
                'recipients' => [
                    ['phone_number' => '+905551234567', 'name' => 'Test User']
                ]
            ]);
            
            $response->assertStatus(201);
        }
        
        // 11th request should be rate limited
        $response = $this->postJson('/api/messages', [
            'content' => 'Test message 11',
            'recipients' => [
                ['phone_number' => '+905551234567', 'name' => 'Test User']
            ]
        ]);
        
        $response->assertStatus(429);
        $response->assertJson([
            'error' => 'Too Many Requests',
            'message' => 'Rate limit exceeded. Please try again later.'
        ]);
        
        // Check rate limit headers
        $response->assertHeader('X-RateLimit-Limit', 10);
        $response->assertHeader('X-RateLimit-Remaining', 0);
    }

    /**
     * Test message listing rate limit
     */
    public function test_message_listing_rate_limit(): void
    {
        // Clear any existing rate limit
        $key = sha1('message.list|127.0.0.1');
        RateLimiter::clear($key);
        
        // Make 30 requests (should all pass)
        for ($i = 0; $i < 30; $i++) {
            $response = $this->getJson('/api/messages');
            $response->assertStatus(200);
        }
        
        // 31st request should be rate limited
        $response = $this->getJson('/api/messages');
        $response->assertStatus(429);
    }

    /**
     * Test rate limit headers are present
     */
    public function test_rate_limit_headers(): void
    {
        $response = $this->getJson('/api/messages');
        
        $response->assertHeader('X-RateLimit-Limit');
        $response->assertHeader('X-RateLimit-Remaining');
    }

    /**
     * Test rate limit resets after time
     */
    public function test_rate_limit_resets_after_time(): void
    {
        // Clear any existing rate limit
        $key = sha1('message.create|127.0.0.1');
        RateLimiter::clear($key);
        
        // Exhaust rate limit
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/messages', [
                'content' => 'Test message ' . $i,
                'recipients' => [
                    ['phone_number' => '+905551234567', 'name' => 'Test User']
                ]
            ]);
        }
        
        // Should be rate limited
        $response = $this->postJson('/api/messages', [
            'content' => 'Test message 11',
            'recipients' => [
                ['phone_number' => '+905551234567', 'name' => 'Test User']
            ]
        ]);
        $response->assertStatus(429);
        
        // Clear rate limit manually (simulating time reset)
        RateLimiter::clear($key);
        
        // Should work again
        $response = $this->postJson('/api/messages', [
            'content' => 'Test message after reset',
            'recipients' => [
                ['phone_number' => '+905551234567', 'name' => 'Test User']
            ]
        ]);
        $response->assertStatus(201);
    }
}
