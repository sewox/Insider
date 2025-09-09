<?php

namespace App\Repositories\Contracts;

use App\Models\Message;
use Illuminate\Database\Eloquent\Collection;

interface MessageRepositoryInterface
{
    /**
     * Tüm mesajları getir
     */
    public function all(): Collection;

    /**
     * ID'ye göre mesaj getir
     */
    public function find(int $id): ?Message;

    /**
     * Mesaj oluştur
     */
    public function create(array $data): Message;

    /**
     * Mesaj güncelle
     */
    public function update(int $id, array $data): bool;

    /**
     * Mesaj sil
     */
    public function delete(int $id): bool;

    /**
     * Bekleyen mesajları getir
     */
    public function getPendingMessages(): Collection;

    /**
     * Gönderilmiş mesajları getir
     */
    public function getSentMessages(): Collection;

    /**
     * Mesaj durumunu güncelle
     */
    public function updateStatus(int $id, string $status, ?string $externalMessageId = null, ?string $errorMessage = null): bool;
}
