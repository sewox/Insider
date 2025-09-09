<?php

namespace App\Console\Commands;

use App\Jobs\SendMessageJob;
use App\Models\Recipient;
use App\Services\MessageService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessMessagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messages:process {--limit=2 : Her seferinde işlenecek mesaj sayısı}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Bekleyen mesajları işle ve SMS gönderim joblarını kuyruğa ekle';

    protected $messageService;

    public function __construct(MessageService $messageService)
    {
        parent::__construct();
        $this->messageService = $messageService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Bekleyen mesajlar işleniyor... (Limit: {$limit})");
        
        try {
            // Bekleyen mesajları getir
            $messages = $this->messageService->getPendingMessages();
            
            if ($messages->isEmpty()) {
                $this->info('İşlenecek bekleyen mesaj bulunamadı.');
                return 0;
            }

            $processedCount = 0;
            $jobCount = 0;

            foreach ($messages->take($limit) as $message) {
                $this->info("Mesaj işleniyor: ID {$message->id}");
                
                // Mesajın bekleyen alıcılarını getir
                $pendingRecipients = $message->recipients()
                    ->where('status', Recipient::STATUS_PENDING)
                    ->get();

                if ($pendingRecipients->isEmpty()) {
                    $this->warn("Mesaj ID {$message->id} için bekleyen alıcı bulunamadı.");
                    continue;
                }

                // Her alıcı için job oluştur
                foreach ($pendingRecipients as $recipient) {
                    SendMessageJob::dispatch($message->id, $recipient->id);
                    $jobCount++;
                    
                    $this->line("  - Alıcı ID {$recipient->id} ({$recipient->phone_number}) için job oluşturuldu");
                }

                $processedCount++;
                
                // 5 saniye bekle (gereksinim: her 5 saniyede 2 mesaj)
                if ($processedCount < $limit) {
                    $this->info("5 saniye bekleniyor...");
                    sleep(5);
                }
            }

            $this->info("İşlem tamamlandı!");
            $this->info("İşlenen mesaj sayısı: {$processedCount}");
            $this->info("Oluşturulan job sayısı: {$jobCount}");
            
            Log::info("ProcessMessagesCommand completed", [
                'processed_messages' => $processedCount,
                'created_jobs' => $jobCount,
                'limit' => $limit,
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error("Hata oluştu: " . $e->getMessage());
            
            Log::error("ProcessMessagesCommand failed", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 1;
        }
    }
}
