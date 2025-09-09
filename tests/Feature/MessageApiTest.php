<?php

namespace Tests\Feature;

use App\Models\Message;
use App\Models\Recipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class MessageApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_create_message_success(): void
    {
        // Arrange
        $data = [
            'content' => 'Test mesajı',
            'recipients' => [
                [
                    'phone_number' => '+905551234567',
                    'name' => 'John Doe'
                ],
                [
                    'phone_number' => '+905559876543',
                    'name' => 'Jane Doe'
                ]
            ]
        ];

        // Act
        $response = $this->postJson('/api/messages', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'content',
                    'status',
                    'created_at',
                    'recipients' => [
                        '*' => [
                            'id',
                            'phone_number',
                            'name',
                            'status'
                        ]
                    ]
                ],
                'message'
            ]);

        $this->assertDatabaseHas('messages', [
            'content' => 'Test mesajı',
            'status' => Message::STATUS_PENDING
        ]);

        $this->assertDatabaseHas('recipients', [
            'phone_number' => '+905551234567',
            'name' => 'John Doe',
            'status' => Recipient::STATUS_PENDING
        ]);
    }

    public function test_create_message_validation_error(): void
    {
        // Arrange
        $data = [
            'content' => '', // Boş içerik
            'recipients' => [] // Boş alıcı listesi
        ];

        // Act
        $response = $this->postJson('/api/messages', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);
    }

    public function test_create_message_content_too_long(): void
    {
        // Arrange
        $data = [
            'content' => str_repeat('a', 161), // 161 karakter
            'recipients' => [
                [
                    'phone_number' => '+905551234567',
                    'name' => 'John Doe'
                ]
            ]
        ];

        // Act
        $response = $this->postJson('/api/messages', $data);

        // Assert
        $response->assertStatus(422)
            ->assertJsonStructure([
                'success',
                'message',
                'errors'
            ]);
    }

    public function test_get_sent_messages(): void
    {
        // Arrange
        $message = Message::factory()->create([
            'status' => Message::STATUS_SENT,
            'external_message_id' => 'msg_123456'
        ]);

        Recipient::factory()->create([
            'message_id' => $message->id,
            'status' => Recipient::STATUS_SENT,
            'external_message_id' => 'msg_123456'
        ]);

        // Act
        $response = $this->getJson('/api/messages');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'content',
                        'status',
                        'external_message_id',
                        'sent_at',
                        'recipients'
                    ]
                ],
                'message'
            ]);
    }

    public function test_get_sent_message_ids(): void
    {
        // Arrange
        $message1 = Message::factory()->create([
            'status' => Message::STATUS_SENT,
            'external_message_id' => 'msg_123456'
        ]);

        $message2 = Message::factory()->create([
            'status' => Message::STATUS_SENT,
            'external_message_id' => 'msg_789012'
        ]);

        // Act
        $response = $this->getJson('/api/messages/sent/list');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'message_ids',
                    'count'
                ],
                'message'
            ]);

        $responseData = $response->json('data');
        $this->assertContains('msg_123456', $responseData['message_ids']);
        $this->assertContains('msg_789012', $responseData['message_ids']);
        $this->assertEquals(2, $responseData['count']);
    }

    public function test_get_message_by_id(): void
    {
        // Arrange
        $message = Message::factory()->create();
        Recipient::factory()->create(['message_id' => $message->id]);

        // Act
        $response = $this->getJson("/api/messages/{$message->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'content',
                    'status',
                    'recipients'
                ],
                'message'
            ]);
    }

    public function test_get_message_by_id_not_found(): void
    {
        // Act
        $response = $this->getJson('/api/messages/999');

        // Assert
        $response->assertStatus(404)
            ->assertJsonStructure([
                'success',
                'message'
            ]);
    }

    public function test_check_message_status(): void
    {
        // Act
        $response = $this->getJson('/api/messages/status/msg_123456');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'message'
            ]);
    }
}
