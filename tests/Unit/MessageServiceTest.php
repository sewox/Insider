<?php

namespace Tests\Unit;

use App\Models\Message;
use App\Models\Recipient;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\Contracts\RecipientRepositoryInterface;
use App\Services\MessageService;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class MessageServiceTest extends TestCase
{
    protected $messageRepository;
    protected $recipientRepository;
    protected $messageService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->messageRepository = Mockery::mock(MessageRepositoryInterface::class);
        $this->recipientRepository = Mockery::mock(RecipientRepositoryInterface::class);
        $this->messageService = new MessageService($this->messageRepository, $this->recipientRepository);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_create_message_with_recipients_success(): void
    {
        // Arrange
        $content = 'Test mesajı';
        $recipients = [
            ['phone_number' => '+905551234567', 'name' => 'John Doe'],
            ['phone_number' => '+905559876543', 'name' => 'Jane Doe'],
        ];

        $message = new Message(['id' => 1, 'content' => $content]);
        $message->setRelation('recipients', new Collection());

        $this->messageRepository
            ->shouldReceive('create')
            ->once()
            ->with(['content' => $content, 'status' => Message::STATUS_PENDING])
            ->andReturn($message);

        $this->recipientRepository
            ->shouldReceive('create')
            ->twice()
            ->andReturn(new Recipient());

        // Act
        $result = $this->messageService->createMessageWithRecipients($content, $recipients);

        // Assert
        $this->assertInstanceOf(Message::class, $result);
        $this->assertEquals($content, $result->content);
    }

    public function test_create_message_with_recipients_content_too_long(): void
    {
        // Arrange
        $content = str_repeat('a', 164); // 164 karakter
        $recipients = [
            ['phone_number' => '+905551234567', 'name' => 'John Doe'],
        ];

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Mesaj içeriği çok uzun. Maksimum 160 karakter olmalıdır.');
        
        $this->messageService->createMessageWithRecipients($content, $recipients);
    }

    public function test_get_pending_messages(): void
    {
        // Arrange
        $messages = new Collection([
            new Message(['id' => 1, 'content' => 'Test 1']),
            new Message(['id' => 2, 'content' => 'Test 2']),
        ]);

        $this->messageRepository
            ->shouldReceive('getPendingMessages')
            ->once()
            ->andReturn($messages);

        // Act
        $result = $this->messageService->getPendingMessages();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(2, $result);
    }

    public function test_get_sent_messages(): void
    {
        // Arrange
        $messages = new Collection([
            new Message(['id' => 1, 'content' => 'Test 1', 'status' => Message::STATUS_SENT]),
        ]);

        $this->messageRepository
            ->shouldReceive('getSentMessages')
            ->once()
            ->andReturn($messages);

        // Act
        $result = $this->messageService->getSentMessages();

        // Assert
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertCount(1, $result);
    }

    public function test_update_message_status_success(): void
    {
        // Arrange
        $messageId = 1;
        $status = Message::STATUS_SENT;
        $externalMessageId = 'msg_123456';

        $this->messageRepository
            ->shouldReceive('updateStatus')
            ->once()
            ->with($messageId, $status, $externalMessageId, null)
            ->andReturn(true);

        // Act
        $result = $this->messageService->updateMessageStatus($messageId, $status, $externalMessageId);

        // Assert
        $this->assertTrue($result);
    }

    public function test_handle_message_send_result_success(): void
    {
        // Arrange
        $messageId = 1;
        $success = true;
        $externalMessageId = 'msg_123456';

        $this->messageRepository
            ->shouldReceive('updateStatus')
            ->once()
            ->with($messageId, Message::STATUS_SENT, $externalMessageId, null)
            ->andReturn(true);

        // Act
        $this->messageService->handleMessageSendResult($messageId, $success, $externalMessageId);

        // Assert - No exception should be thrown
        $this->assertTrue(true);
    }

    public function test_handle_message_send_result_failure(): void
    {
        // Arrange
        $messageId = 1;
        $success = false;
        $errorMessage = 'SMS gönderimi başarısız';

        $this->messageRepository
            ->shouldReceive('updateStatus')
            ->once()
            ->with($messageId, Message::STATUS_FAILED, null, $errorMessage)
            ->andReturn(true);

        // Act
        $this->messageService->handleMessageSendResult($messageId, $success, null, $errorMessage);

        // Assert - No exception should be thrown
        $this->assertTrue(true);
    }
}
