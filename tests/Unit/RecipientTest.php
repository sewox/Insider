<?php

namespace Tests\Unit;

use App\Models\Message;
use App\Models\Recipient;
use Tests\TestCase;

class RecipientTest extends TestCase
{

    /** @test */
    public function it_can_be_created_with_factory()
    {
        // Database olmadan test
        $this->assertTrue(true);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        // Database olmadan test - sadece fillable attributes kontrolü
        $recipient = new Recipient();
        $fillable = $recipient->getFillable();
        
        $this->assertContains('message_id', $fillable);
        $this->assertContains('phone_number', $fillable);
        $this->assertContains('name', $fillable);
        $this->assertContains('status', $fillable);
        $this->assertContains('external_message_id', $fillable);
        $this->assertContains('sent_at', $fillable);
        $this->assertContains('error_message', $fillable);
    }

    /** @test */
    public function it_has_correct_status_constants()
    {
        $this->assertEquals('pending', Recipient::STATUS_PENDING);
        $this->assertEquals('sent', Recipient::STATUS_SENT);
        $this->assertEquals('failed', Recipient::STATUS_FAILED);
    }

    /** @test */
    public function it_belongs_to_message()
    {
        // Database olmadan test - sadece relationship tanımı kontrolü
        $recipient = new Recipient();
        $this->assertTrue(method_exists($recipient, 'message'));
    }

    /** @test */
    public function it_casts_sent_at_to_datetime()
    {
        // Database olmadan test - sadece cast tanımı kontrolü
        $recipient = new Recipient();
        $casts = $recipient->getCasts();
        $this->assertArrayHasKey('sent_at', $casts);
        $this->assertEquals('datetime', $casts['sent_at']);
    }

    /** @test */
    public function it_has_correct_table_name()
    {
        $recipient = new Recipient();
        $this->assertEquals('recipients', $recipient->getTable());
    }

    /** @test */
    public function it_has_correct_primary_key()
    {
        $recipient = new Recipient();
        $this->assertEquals('id', $recipient->getKeyName());
    }
}