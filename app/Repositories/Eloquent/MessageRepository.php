<?php

namespace App\Repositories\Eloquent;

use App\Models\Message;
use App\Repositories\Contracts\MessageRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MessageRepository implements MessageRepositoryInterface
{
    protected $model;

    public function __construct(Message $model)
    {
        $this->model = $model;
    }

    /**
     * Tüm mesajları getir
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * ID'ye göre mesaj getir
     */
    public function find(int $id): ?Message
    {
        return $this->model->find($id);
    }

    /**
     * Mesaj oluştur
     */
    public function create(array $data): Message
    {
        return $this->model->create($data);
    }

    /**
     * Mesaj güncelle
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Mesaj sil
     */
    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }

    /**
     * Bekleyen mesajları getir
     */
    public function getPendingMessages(): Collection
    {
        return $this->model->pending()->with('recipients')->get();
    }

    /**
     * Gönderilmiş mesajları getir
     */
    public function getSentMessages(): Collection
    {
        return $this->model->sent()->with('recipients')->get();
    }

    /**
     * Mesaj durumunu güncelle
     */
    public function updateStatus(int $id, string $status, ?string $externalMessageId = null, ?string $errorMessage = null): bool
    {
        $updateData = [
            'status' => $status,
            'sent_at' => $status === Message::STATUS_SENT ? now() : null,
        ];

        if ($externalMessageId) {
            $updateData['external_message_id'] = $externalMessageId;
        }

        if ($errorMessage) {
            $updateData['error_message'] = $errorMessage;
        }

        return $this->model->where('id', $id)->update($updateData);
    }
}
