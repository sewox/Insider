<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Recipient;
use App\Repositories\Contracts\MessageRepositoryInterface;
use App\Repositories\Contracts\RecipientRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

class MessageService
{
    protected $messageRepository;
    protected $recipientRepository;

    public function __construct(
        MessageRepositoryInterface $messageRepository,
        RecipientRepositoryInterface $recipientRepository
    ) {
        $this->messageRepository = $messageRepository;
        $this->recipientRepository = $recipientRepository;
    }

    /**
     * Tüm mesajları getir
     */
    public function getAllMessages(): Collection
    {
        return $this->messageRepository->all();
    }

    /**
     * ID'ye göre mesaj getir
     */
    public function getMessageById(int $id): ?Message
    {
        return $this->messageRepository->find($id);
    }

    /**
     * Yeni mesaj oluştur
     */
    public function createMessage(array $data): Message
    {
        return $this->messageRepository->create($data);
    }

    /**
     * Mesaj güncelle
     */
    public function updateMessage(int $id, array $data): bool
    {
        return $this->messageRepository->update($id, $data);
    }

    /**
     * Mesaj sil
     */
    public function deleteMessage(int $id): bool
    {
        return $this->messageRepository->delete($id);
    }

    /**
     * Bekleyen mesajları getir
     */
    public function getPendingMessages(): Collection
    {
        return $this->messageRepository->getPendingMessages();
    }

    /**
     * Gönderilmiş mesajları getir
     */
    public function getSentMessages(): Collection
    {
        return $this->messageRepository->getSentMessages();
    }

    /**
     * Mesaj durumunu güncelle
     */
    public function updateMessageStatus(int $id, string $status, ?string $externalMessageId = null, ?string $errorMessage = null): bool
    {
        return $this->messageRepository->updateStatus($id, $status, $externalMessageId, $errorMessage);
    }

    /**
     * Mesaj ve alıcıları oluştur
     */
    public function createMessageWithRecipients(string $content, array $recipients): Message
    {
        // Mesaj içeriği karakter sınırı kontrolü
        if (strlen($content) > 140) { // SMS için genel limit
            throw new \InvalidArgumentException('Mesaj içeriği çok uzun. Maksimum 140 karakter olmalıdır.');
        }

        // Mesaj oluştur
        $message = $this->messageRepository->create([
            'content' => $content,
            'status' => Message::STATUS_PENDING,
        ]);

        // Alıcıları oluştur
        foreach ($recipients as $recipientData) {
            $this->recipientRepository->create([
                'message_id' => $message->id,
                'phone_number' => $recipientData['phone_number'],
                'name' => $recipientData['name'] ?? null,
                'status' => Recipient::STATUS_PENDING,
            ]);
        }

        return $message->load('recipients');
    }

    /**
     * Gönderilecek mesajları işle (Job için)
     */
    public function processPendingMessages(int $limit = 2): Collection
    {
        $messages = $this->messageRepository->getPendingMessages();
        
        return $messages->take($limit);
    }

    /**
     * Mesaj gönderim sonucunu işle
     */
    public function handleMessageSendResult(int $messageId, bool $success, ?string $externalMessageId = null, ?string $errorMessage = null): void
    {
        $status = $success ? Message::STATUS_SENT : Message::STATUS_FAILED;
        
        $this->messageRepository->updateStatus($messageId, $status, $externalMessageId, $errorMessage);
        
        Log::info("Message {$messageId} status updated to {$status}", [
            'message_id' => $messageId,
            'status' => $status,
            'external_message_id' => $externalMessageId,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Alıcı gönderim sonucunu işle
     */
    public function handleRecipientSendResult(int $recipientId, bool $success, ?string $externalMessageId = null, ?string $errorMessage = null): void
    {
        $status = $success ? Recipient::STATUS_SENT : Recipient::STATUS_FAILED;
        
        $this->recipientRepository->updateStatus($recipientId, $status, $externalMessageId, $errorMessage);
        
        Log::info("Recipient {$recipientId} status updated to {$status}", [
            'recipient_id' => $recipientId,
            'status' => $status,
            'external_message_id' => $externalMessageId,
            'error_message' => $errorMessage,
        ]);
    }
}
