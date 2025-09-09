<?php

namespace App\Jobs;

use App\Models\Message;
use App\Models\Recipient;
use App\Services\MessageService;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 120; // 2 dakika timeout
    public $tries = 3; // 3 kez dene

    protected $messageId;
    protected $recipientId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $messageId, int $recipientId)
    {
        $this->messageId = $messageId;
        $this->recipientId = $recipientId;
    }

    /**
     * Execute the job.
     */
    public function handle(MessageService $messageService, SmsService $smsService): void
    {
        try {
            // Mesaj ve alıcı bilgilerini getir
            $message = $messageService->getMessageById($this->messageId);
            $recipient = Recipient::find($this->recipientId);

            if (!$message || !$recipient) {
                Log::error("Message or recipient not found", [
                    'message_id' => $this->messageId,
                    'recipient_id' => $this->recipientId,
                ]);
                return;
            }

            // SMS gönder
            $result = $smsService->sendSms($recipient->phone_number, $message->content);

            if ($result['success']) {
                // Başarılı gönderim
                $messageService->handleRecipientSendResult(
                    $this->recipientId,
                    true,
                    $result['message_id'],
                    null
                );

                // Message ID'yi cache'le 
                $smsService->cacheMessageId($result['message_id'], $recipient->phone_number);

                Log::info("SMS sent successfully via job", [
                    'message_id' => $this->messageId,
                    'recipient_id' => $this->recipientId,
                    'phone_number' => $recipient->phone_number,
                    'external_message_id' => $result['message_id'],
                ]);
            } else {
                // Başarısız gönderim
                $messageService->handleRecipientSendResult(
                    $this->recipientId,
                    false,
                    null,
                    $result['error']
                );

                Log::error("SMS sending failed via job", [
                    'message_id' => $this->messageId,
                    'recipient_id' => $this->recipientId,
                    'phone_number' => $recipient->phone_number,
                    'error' => $result['error'],
                ]);
            }

            // Mesajın tüm alıcıları gönderildi mi kontrol et
            $this->checkMessageCompletion($messageService, $message);

        } catch (\Exception $e) {
            Log::error("SendMessageJob exception", [
                'message_id' => $this->messageId,
                'recipient_id' => $this->recipientId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Hata durumunda alıcı durumunu güncelle
            $messageService->handleRecipientSendResult(
                $this->recipientId,
                false,
                null,
                'Job execution error: ' . $e->getMessage()
            );

            throw $e; // Job'u tekrar denemek için
        }
    }

    /**
     * Mesajın tüm alıcıları gönderildi mi kontrol et
     */
    private function checkMessageCompletion(MessageService $messageService, Message $message): void
    {
        $pendingRecipients = $message->recipients()->where('status', Recipient::STATUS_PENDING)->count();
        
        if ($pendingRecipients === 0) {
            // Tüm alıcılar işlendi, mesaj durumunu güncelle
            $failedRecipients = $message->recipients()->where('status', Recipient::STATUS_FAILED)->count();
            $sentRecipients = $message->recipients()->where('status', Recipient::STATUS_SENT)->count();
            
            if ($sentRecipients > 0 && $failedRecipients === 0) {
                // Tüm alıcılara başarıyla gönderildi
                $messageService->updateMessageStatus($message->id, Message::STATUS_SENT);
            } elseif ($sentRecipients > 0 && $failedRecipients > 0) {
                // Kısmen başarılı (bazı alıcılara gönderildi, bazılarına gönderilemedi)
                $messageService->updateMessageStatus($message->id, Message::STATUS_SENT);
            } else {
                // Hiçbir alıcıya gönderilemedi
                $messageService->updateMessageStatus($message->id, Message::STATUS_FAILED, null, 'Tüm alıcılara gönderim başarısız');
            }
        }
    }

    /**
     * Job başarısız olduğunda çalışır
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("SendMessageJob failed permanently", [
            'message_id' => $this->messageId,
            'recipient_id' => $this->recipientId,
            'error' => $exception->getMessage(),
        ]);

        // Son deneme başarısız olduğunda alıcı durumunu güncelle
        app(MessageService::class)->handleRecipientSendResult(
            $this->recipientId,
            false,
            null,
            'Job permanently failed: ' . $exception->getMessage()
        );
    }
}
