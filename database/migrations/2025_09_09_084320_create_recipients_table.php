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
        Schema::create('recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained()->onDelete('cascade'); // Mesaj ID'si
            $table->string('phone_number'); // Telefon numarası
            $table->string('name')->nullable(); // Alıcı adı (opsiyonel)
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending'); // Gönderim durumu
            $table->string('external_message_id')->nullable(); // Dış servisten dönen mesaj ID'si
            $table->timestamp('sent_at')->nullable(); // Gönderim zamanı
            $table->text('error_message')->nullable(); // Hata mesajı
            $table->timestamps();
            
            $table->index(['message_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('recipients');
    }
};
