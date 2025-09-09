<?php

namespace App\Repositories\Contracts;

use App\Models\Recipient;
use Illuminate\Database\Eloquent\Collection;

interface RecipientRepositoryInterface
{
    /**
     * Tüm alıcıları getir
     */
    public function all(): Collection;

    /**
     * ID'ye göre alıcı getir
     */
    public function find(int $id): ?Recipient;

    /**
     * Alıcı oluştur
     */
    public function create(array $data): Recipient;

    /**
     * Alıcı güncelle
     */
    public function update(int $id, array $data): bool;

    /**
     * Alıcı sil
     */
    public function delete(int $id): bool;

    /**
     * Mesaj ID'sine göre alıcıları getir
     */
    public function getByMessageId(int $messageId): Collection;

    /**
     * Bekleyen alıcıları getir
     */
    public function getPendingRecipients(): Collection;

    /**
     * Gönderilmiş alıcıları getir
     */
    public function getSentRecipients(): Collection;

    /**
     * Alıcı durumunu güncelle
     */
    public function updateStatus(int $id, string $status, ?string $externalMessageId = null, ?string $errorMessage = null): bool;

    /**
     * Mesaj ID'sine göre bekleyen alıcıları getir
     */
    public function getPendingRecipientsByMessageId(int $messageId): Collection;
}
