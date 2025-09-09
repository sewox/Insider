<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->text('content'); // Mesaj içeriği
            $table->string('external_message_id')->nullable(); // Dış servisten dönen mesaj ID'si
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending'); // Gönderim durumu
            $table->timestamp('sent_at')->nullable(); // Gönderim zamanı
            $table->text('error_message')->nullable(); // Hata mesajı
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
