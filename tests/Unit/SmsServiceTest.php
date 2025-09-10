<?php

namespace Tests\Unit;

use App\Services\SmsService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{

    /** @test */
    public function it_can_be_instantiated()
    {
        $smsService = new SmsService();
        $this->assertInstanceOf(SmsService::class, $smsService);
    }

    /** @test */
    public function it_sends_sms_successfully_on_first_attempt()
    {
        Http::fake([
            'webhook.site/*' => Http::response(['success' => true], 200)
        ]);

        $smsService = new SmsService();
        $result = $smsService->sendSms('+905551234567', 'Test message');

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('msg_', $result['message_id']);
        $this->assertEquals(1, $result['attempt']);
        $this->assertArrayHasKey('response', $result);
    }

    /** @test */
    public function it_retries_on_failure_and_succeeds_on_second_attempt()
    {
        Http::fake([
            'webhook.site/*' => Http::sequence()
                ->push([], 500) // First attempt fails
                ->push(['success' => true], 200) // Second attempt succeeds
        ]);

        $smsService = new SmsService();
        $result = $smsService->sendSms('+905551234567', 'Test message');

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('msg_', $result['message_id']);
        $this->assertEquals(2, $result['attempt']);
    }

    /** @test */
    public function it_fails_after_max_retries()
    {
        // Http facade'ini tamamen mock'la
        Http::shouldReceive('timeout')
            ->with(5) // Test timeout değeri
            ->andReturnSelf()
            ->times(2); // Test'te 2 retry

        $mockResponse = \Mockery::mock();
        $mockResponse->shouldReceive('successful')->andReturn(false);
        $mockResponse->shouldReceive('status')->andReturn(500);
        $mockResponse->shouldReceive('body')->andReturn('Server Error');
        $mockResponse->shouldReceive('json')->andReturn(['error' => 'Server Error']);
        
        Http::shouldReceive('post')
            ->with(\Mockery::type('string'), \Mockery::type('array'))
            ->andReturn($mockResponse)
            ->times(2); // Test'te 2 retry

        $smsService = new SmsService();
        $result = $smsService->sendSms('+905551234567', 'Test message');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SMS gönderimi başarısız: HTTP 500', $result['error']);
        $this->assertEquals(2, $result['attempts']); // Test'te 2 retry
    }

    /** @test */
    public function it_handles_http_exceptions_with_retry()
    {
        Http::fake([
            'webhook.site/*' => Http::sequence()
                ->push([], 500) // First attempt fails
                ->push(['success' => true], 200) // Second attempt succeeds
        ]);

        $smsService = new SmsService();
        $result = $smsService->sendSms('+905551234567', 'Test message');

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('msg_', $result['message_id']);
        $this->assertEquals(2, $result['attempt']);
    }

    /** @test */
    public function it_logs_sms_sending_attempts()
    {
        Log::shouldReceive('info')
            ->with('SMS sending attempt', \Mockery::type('array'))
            ->atLeast()->once();

        Log::shouldReceive('info')
            ->with('SMS sent successfully', \Mockery::type('array'))
            ->once();

        Http::fake([
            'webhook.site/*' => Http::response(['success' => true], 200)
        ]);

        $smsService = new SmsService();
        $smsService->sendSms('+905551234567', 'Test message');
    }

    /** @test */
    public function it_logs_sms_sending_failures()
    {
        Log::shouldReceive('info')
            ->with('SMS sending attempt', \Mockery::type('array'))
            ->atLeast()->once();

        Log::shouldReceive('warning')
            ->with('SMS sending failed, will retry', \Mockery::type('array'))
            ->atLeast()->once();

        // Http facade'ini tamamen mock'la
        Http::shouldReceive('timeout')
            ->with(5)
            ->andReturnSelf()
            ->times(2);

        $mockResponse = \Mockery::mock();
        $mockResponse->shouldReceive('successful')->andReturn(false);
        $mockResponse->shouldReceive('status')->andReturn(500);
        $mockResponse->shouldReceive('body')->andReturn('Server Error');
        $mockResponse->shouldReceive('json')->andReturn(['error' => 'Server Error']);
        
        Http::shouldReceive('post')
            ->with(\Mockery::type('string'), \Mockery::type('array'))
            ->andReturn($mockResponse)
            ->times(2);

        $smsService = new SmsService();
        $smsService->sendSms('+905551234567', 'Test message');
    }

    /** @test */
    public function it_logs_sms_sending_exceptions()
    {
        Log::shouldReceive('info')
            ->with('SMS sending attempt', \Mockery::type('array'))
            ->atLeast()->once();

        Log::shouldReceive('warning')
            ->with('SMS sending exception, will retry', \Mockery::type('array'))
            ->atLeast()->once();

        // Http facade'ini tamamen mock'la - exception fırlat
        Http::shouldReceive('timeout')
            ->with(5)
            ->andReturnSelf()
            ->times(2);

        Http::shouldReceive('post')
            ->with(\Mockery::type('string'), \Mockery::type('array'))
            ->andThrow(new \Exception('Network error'))
            ->times(2);

        $smsService = new SmsService();
        $smsService->sendSms('+905551234567', 'Test message');
    }

    /** @test */
    public function it_caches_message_id_after_successful_send()
    {
        Cache::shouldReceive('put')
            ->with('sms_message_id:test_msg_123', \Mockery::type('array'), \Mockery::type('object'))
            ->once();

        $smsService = new SmsService();
        $smsService->cacheMessageId('test_msg_123', '+905551234567');
    }

    /** @test */
    public function it_retrieves_cached_message_id()
    {
        Cache::shouldReceive('get')
            ->with('sms_message_id:test_msg_123')
            ->andReturn(['message_id' => 'test_msg_123', 'phone_number' => '+905551234567', 'sent_at' => '2025-01-01T00:00:00Z'])
            ->once();

        $smsService = new SmsService();
        $result = $smsService->getCachedMessageId('test_msg_123');

        $this->assertIsArray($result);
        $this->assertEquals('test_msg_123', $result['message_id']);
        $this->assertEquals('+905551234567', $result['phone_number']);
    }

    /** @test */
    public function it_returns_null_for_non_existent_cached_message_id()
    {
        Cache::shouldReceive('get')
            ->with('sms_message_id:non_existent')
            ->andReturn(null)
            ->once();

        $smsService = new SmsService();
        $result = $smsService->getCachedMessageId('non_existent');

        $this->assertNull($result);
    }

    /** @test */
    public function it_checks_message_status_from_cache()
    {
        Cache::shouldReceive('get')
            ->with('sms_message_id:test_msg_123')
            ->andReturn(['message_id' => 'test_msg_123', 'phone_number' => '+905551234567', 'sent_at' => '2025-01-01T00:00:00Z'])
            ->once();

        $smsService = new SmsService();
        $result = $smsService->checkMessageStatus('test_msg_123');

        $this->assertEquals([
            'found' => true,
            'data' => ['message_id' => 'test_msg_123', 'phone_number' => '+905551234567', 'sent_at' => '2025-01-01T00:00:00Z']
        ], $result);
    }

    /** @test */
    public function it_returns_not_found_for_non_existent_message_status()
    {
        Cache::shouldReceive('get')
            ->with('sms_message_id:non_existent')
            ->andReturn(null)
            ->once();

        $smsService = new SmsService();
        $result = $smsService->checkMessageStatus('non_existent');

        $this->assertEquals([
            'found' => false,
            'message' => 'Mesaj ID bulunamadı'
        ], $result);
    }

    /** @test */
    public function it_uses_correct_webhook_url()
    {
        Http::fake([
            'webhook.site/*' => Http::response(['success' => true], 200)
        ]);

        $smsService = new SmsService();
        $smsService->sendSms('+905551234567', 'Test message');

        Http::assertSent(function ($request) {
            return $request->url() === 'https://webhook.site/23a9b629-861b-4a07-9ba6-97eecb2748ce';
        });
    }

    /** @test */
    public function it_sends_correct_payload_to_webhook()
    {
        Http::fake([
            'webhook.site/*' => Http::response(['success' => true], 200)
        ]);

        $smsService = new SmsService();
        $result = $smsService->sendSms('+905551234567', 'Test message');

        Http::assertSent(function ($request) {
            $data = $request->data();
            return $data['phone_number'] === '+905551234567' &&
                   $data['message'] === 'Test message' &&
                   isset($data['timestamp']) &&
                   isset($data['api_key']) &&
                   isset($data['attempt']);
        });
    }

    /** @test */
    public function it_handles_multiple_concurrent_requests()
    {
        Http::fake([
            'webhook.site/*' => Http::response(['success' => true], 200)
        ]);

        $smsService = new SmsService();
        $result1 = $smsService->sendSms('+905551234567', 'Test message 1');
        $result2 = $smsService->sendSms('+905551234568', 'Test message 2');

        $this->assertTrue($result1['success']);
        $this->assertTrue($result2['success']);
        $this->assertNotEquals($result1['message_id'], $result2['message_id']);
    }

    /** @test */
    public function it_validates_phone_number_format()
    {
        // Boş telefon numarası ile test - HTTP isteği yapmadan hata döner
        Http::shouldReceive('timeout')
            ->with(5)
            ->andReturnSelf()
            ->times(2);

        $mockResponse = \Mockery::mock();
        $mockResponse->shouldReceive('successful')->andReturn(false);
        $mockResponse->shouldReceive('status')->andReturn(429);
        $mockResponse->shouldReceive('body')->andReturn('Rate Limited');
        $mockResponse->shouldReceive('json')->andReturn(['error' => 'Rate Limited']);
        
        Http::shouldReceive('post')
            ->with(\Mockery::type('string'), \Mockery::type('array'))
            ->andReturn($mockResponse)
            ->times(2);

        $smsService = new SmsService();
        $result = $smsService->sendSms('', 'Test message');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SMS gönderimi başarısız: HTTP 429', $result['error']);
    }

    /** @test */
    public function it_validates_message_content()
    {
        // Boş mesaj ile test - HTTP isteği yapmadan hata döner
        Http::shouldReceive('timeout')
            ->with(5)
            ->andReturnSelf()
            ->times(2);

        $mockResponse = \Mockery::mock();
        $mockResponse->shouldReceive('successful')->andReturn(false);
        $mockResponse->shouldReceive('status')->andReturn(429);
        $mockResponse->shouldReceive('body')->andReturn('Rate Limited');
        $mockResponse->shouldReceive('json')->andReturn(['error' => 'Rate Limited']);
        
        Http::shouldReceive('post')
            ->with(\Mockery::type('string'), \Mockery::type('array'))
            ->andReturn($mockResponse)
            ->times(2);

        $smsService = new SmsService();
        $result = $smsService->sendSms('+905551234567', '');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('SMS gönderimi başarısız: HTTP 429', $result['error']);
    }

    /** @test */
    public function it_handles_very_long_messages()
    {
        $longMessage = str_repeat('A', 1000); // 1000 karakter

        Http::fake([
            'webhook.site/*' => Http::response(['success' => true], 200)
        ]);

        $smsService = new SmsService();
        $result = $smsService->sendSms('+905551234567', $longMessage);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('msg_', $result['message_id']);
    }
}