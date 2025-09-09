<?php

namespace App\Repositories\Eloquent;

use App\Models\Recipient;
use App\Repositories\Contracts\RecipientRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class RecipientRepository implements RecipientRepositoryInterface
{
    protected $model;

    public function __construct(Recipient $model)
    {
        $this->model = $model;
    }

    /**
     * Tüm alıcıları getir
     */
    public function all(): Collection
    {
        return $this->model->all();
    }

    /**
     * ID'ye göre alıcı getir
     */
    public function find(int $id): ?Recipient
    {
        return $this->model->find($id);
    }

    /**
     * Alıcı oluştur
     */
    public function create(array $data): Recipient
    {
        return $this->model->create($data);
    }

    /**
     * Alıcı güncelle
     */
    public function update(int $id, array $data): bool
    {
        return $this->model->where('id', $id)->update($data);
    }

    /**
     * Alıcı sil
     */
    public function delete(int $id): bool
    {
        return $this->model->destroy($id);
    }

    /**
     * Mesaj ID'sine göre alıcıları getir
     */
    public function getByMessageId(int $messageId): Collection
    {
        return $this->model->where('message_id', $messageId)->get();
    }

    /**
     * Bekleyen alıcıları getir
     */
    public function getPendingRecipients(): Collection
    {
        return $this->model->pending()->with('message')->get();
    }

    /**
     * Gönderilmiş alıcıları getir
     */
    public function getSentRecipients(): Collection
    {
        return $this->model->sent()->with('message')->get();
    }

    /**
     * Alıcı durumunu güncelle
     */
    public function updateStatus(int $id, string $status, ?string $externalMessageId = null, ?string $errorMessage = null): bool
    {
        $updateData = [
            'status' => $status,
            'sent_at' => $status === Recipient::STATUS_SENT ? now() : null,
        ];

        if ($externalMessageId) {
            $updateData['external_message_id'] = $externalMessageId;
        }

        if ($errorMessage) {
            $updateData['error_message'] = $errorMessage;
        }

        return $this->model->where('id', $id)->update($updateData);
    }

    /**
     * Mesaj ID'sine göre bekleyen alıcıları getir
     */
    public function getPendingRecipientsByMessageId(int $messageId): Collection
    {
        return $this->model->where('message_id', $messageId)
            ->pending()
            ->get();
    }
}
