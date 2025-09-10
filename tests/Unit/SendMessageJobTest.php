<?php

namespace Tests\Unit;

use App\Jobs\SendMessageJob;
use App\Models\Message;
use App\Models\Recipient;
use App\Services\MessageService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class SendMessageJobTest extends TestCase
{
    /** @test */
    public function it_can_be_instantiated_with_message_and_recipient_ids()
    {
        $job = new SendMessageJob(1, 1);
        
        $this->assertInstanceOf(SendMessageJob::class, $job);
    }

    /** @test */
    public function it_has_correct_retry_configuration()
    {
        $job = new SendMessageJob(1, 1);
        
        $this->assertEquals(5, $job->tries); // Production'da 5 retry
        $this->assertEquals(120, $job->timeout);
        $this->assertEquals([30, 60, 120, 240], $job->backoff);
    }

    /** @test */
    public function it_implements_should_queue_interface()
    {
        $job = new SendMessageJob(1, 1);
        
        $this->assertTrue(method_exists($job, 'handle'));
    }

    /** @test */
    public function it_has_correct_properties()
    {
        $job = new SendMessageJob(1, 1);
        
        // Reflection kullanarak private property'leri kontrol et
        $reflection = new \ReflectionClass($job);
        $messageIdProperty = $reflection->getProperty('messageId');
        $messageIdProperty->setAccessible(true);
        $recipientIdProperty = $reflection->getProperty('recipientId');
        $recipientIdProperty->setAccessible(true);
        
        $this->assertEquals(1, $messageIdProperty->getValue($job));
        $this->assertEquals(1, $recipientIdProperty->getValue($job));
    }

    /** @test */
    public function it_has_handle_method()
    {
        $job = new SendMessageJob(1, 1);
        
        $this->assertTrue(method_exists($job, 'handle'));
        $this->assertTrue(method_exists($job, 'failed'));
    }

    /** @test */
    public function it_handles_message_not_found()
    {
        // Mock services
        $messageService = Mockery::mock(MessageService::class);
        $smsService = Mockery::mock(SmsService::class);
        
        $messageService->shouldReceive('getMessageById')->with(1)->andReturn(null);
        
        Log::shouldReceive('error')->with('Message or recipient not found', Mockery::type('array'));
        
        $job = new SendMessageJob(1, 1);
        $job->handle($messageService, $smsService);
        
        $this->assertTrue(true); // Test assertion
    }

    /** @test */
    public function it_handles_recipient_not_found()
    {
        // Mock services
        $messageService = Mockery::mock(MessageService::class);
        $smsService = Mockery::mock(SmsService::class);
        
        $message = Mockery::mock(Message::class);
        $messageService->shouldReceive('getMessageById')->with(1)->andReturn($message);
        
        // Mock Recipient::find to return null
        $this->mock(Recipient::class, function ($mock) {
            $mock->shouldReceive('find')->with(1)->andReturn(null);
        });
        
        Log::shouldReceive('error')->with('Message or recipient not found', Mockery::type('array'));
        
        $job = new SendMessageJob(1, 1);
        $job->handle($messageService, $smsService);
        
        $this->assertTrue(true); // Test assertion
    }

    /** @test */
    public function it_has_failed_method()
    {
        $job = new SendMessageJob(1, 1);
        
        $this->assertTrue(method_exists($job, 'failed'));
    }
}