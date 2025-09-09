<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class SmsService
{
    protected $webhookUrl;
    protected $apiKey;

    public function __construct()
    {
        $this->webhookUrl = config('services.sms.webhook_url', '');
        $this->apiKey = config('services.sms.api_key', 'your-api-key');
    }

    /**
     * SMS gönder
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        try {
            // Webhook.site'a POST isteği gönder (simülasyon)
            $response = Http::timeout(30)->post($this->webhookUrl, [
                'phone_number' => $phoneNumber,
                'message' => $message,
                'timestamp' => now()->toISOString(),
                'api_key' => $this->apiKey,
            ]);

            if ($response->successful()) {
                // Başarılı gönderim için rastgele message ID oluştur
                $messageId = 'msg_' . uniqid();
                
                Log::info("SMS sent successfully", [
                    'phone_number' => $phoneNumber,
                    'message_id' => $messageId,
                    'response_status' => $response->status(),
                ]);

                return [
                    'success' => true,
                    'message_id' => $messageId,
                    'response' => $response->json(),
                ];
            } else {
                Log::error("SMS sending failed", [
                    'phone_number' => $phoneNumber,
                    'response_status' => $response->status(),
                    'response_body' => $response->body(),
                ]);

                return [
                    'success' => false,
                    'error' => 'SMS gönderimi başarısız: HTTP ' . $response->status(),
                ];
            }
        } catch (\Exception $e) {
            Log::error("SMS sending exception", [
                'phone_number' => $phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => 'SMS gönderimi sırasında hata: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Mesaj ID'sini Redis'e cache'le
     */
    public function cacheMessageId(string $messageId, string $phoneNumber): void
    {
        $cacheKey = "sms_message_id:{$messageId}";
        $cacheData = [
            'message_id' => $messageId,
            'phone_number' => $phoneNumber,
            'sent_at' => now()->toISOString(),
        ];

        // 7 gün cache'le
        Cache::put($cacheKey, $cacheData, now()->addDays(7));
        
        Log::info("Message ID cached", [
            'message_id' => $messageId,
            'phone_number' => $phoneNumber,
        ]);
    }

    /**
     * Cache'den mesaj ID'sini getir
     */
    public function getCachedMessageId(string $messageId): ?array
    {
        $cacheKey = "sms_message_id:{$messageId}";
        return Cache::get($cacheKey);
    }

    /**
     * Mesaj gönderim durumunu kontrol et
     */
    public function checkMessageStatus(string $messageId): array
    {
        $cachedData = $this->getCachedMessageId($messageId);
        
        if ($cachedData) {
            return [
                'found' => true,
                'data' => $cachedData,
            ];
        }

        return [
            'found' => false,
            'message' => 'Mesaj ID bulunamadı',
        ];
    }
}
