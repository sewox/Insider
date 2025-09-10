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
     * SMS gönder (retry mekanizması ile)
     */
    public function sendSms(string $phoneNumber, string $message): array
    {
        $maxRetries = 5;
        // Test environment'ında hızlı retry, production'da normal süre
        $retryDelay = app()->environment('testing') ? 0.1 : 2; // 0.1s test, 2s production
        
        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                Log::info("SMS sending attempt", [
                    'phone_number' => $phoneNumber,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                ]);

                // Webhook.site'a POST isteği gönder (simülasyon)
                $timeout = app()->environment('testing') ? 5 : 30; // Test'te 5s, production'da 30s
                $response = Http::timeout($timeout)->post($this->webhookUrl, [
                    'phone_number' => $phoneNumber,
                    'message' => $message,
                    'timestamp' => now()->toISOString(),
                    'api_key' => $this->apiKey,
                    'attempt' => $attempt,
                ]);

                if ($response->successful()) {
                    // Başarılı gönderim için rastgele message ID oluştur
                    $messageId = 'msg_' . uniqid();
                    
                    Log::info("SMS sent successfully", [
                        'phone_number' => $phoneNumber,
                        'message_id' => $messageId,
                        'response_status' => $response->status(),
                        'attempt' => $attempt,
                    ]);

                    return [
                        'success' => true,
                        'message_id' => $messageId,
                        'response' => $response->json(),
                        'attempt' => $attempt,
                    ];
                } else {
                    Log::warning("SMS sending failed, will retry", [
                        'phone_number' => $phoneNumber,
                        'response_status' => $response->status(),
                        'response_body' => $response->body(),
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries,
                    ]);

                    // Son deneme değilse bekle ve tekrar dene
                    if ($attempt < $maxRetries) {
                        // Test environment'ında sleep yapma
                        if (!app()->environment('testing')) {
                            sleep($retryDelay * $attempt); // Exponential backoff
                        }
                        continue;
                    }

                    // Son deneme başarısız
                    return [
                        'success' => false,
                        'error' => 'SMS gönderimi başarısız: HTTP ' . $response->status() . ' (Tüm denemeler tükendi)',
                        'attempts' => $attempt,
                    ];
                }
            } catch (\Exception $e) {
                Log::warning("SMS sending exception, will retry", [
                    'phone_number' => $phoneNumber,
                    'error' => $e->getMessage(),
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                ]);

                // Son deneme değilse bekle ve tekrar dene
                if ($attempt < $maxRetries) {
                    // Test environment'ında sleep yapma
                    if (!app()->environment('testing')) {
                        sleep($retryDelay * $attempt); // Exponential backoff
                    }
                    continue;
                }

                // Son deneme başarısız
                return [
                    'success' => false,
                    'error' => 'SMS gönderimi sırasında hata: ' . $e->getMessage() . ' (Tüm denemeler tükendi)',
                    'attempts' => $attempt,
                ];
            }
        }

        // Bu noktaya ulaşmamalı, ama güvenlik için
        return [
            'success' => false,
            'error' => 'SMS gönderimi başarısız: Beklenmeyen hata',
            'attempts' => $maxRetries,
        ];
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
