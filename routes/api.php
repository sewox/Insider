<?php

use App\Http\Controllers\Api\MessageController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Mesaj API Routes with Rate Limiting
Route::prefix('messages')->group(function () {
    // Mesaj oluşturma - 10 istek/dakika
    Route::post('/', [MessageController::class, 'store'])
        ->middleware('rate.limit:message.create,10,1');
    
    // Mesaj listeleme - 30 istek/dakika
    Route::get('/', [MessageController::class, 'index'])
        ->middleware('rate.limit:message.list,30,1');
    
    // Gönderilmiş mesaj ID'lerini listele - 20 istek/dakika
    Route::get('/sent/list', [MessageController::class, 'sentList'])
        ->middleware('rate.limit:message.sent,20,1');
    
    // Mesaj durumunu kontrol et - 60 istek/dakika
    Route::get('/status/{messageId}', [MessageController::class, 'checkStatus'])
        ->middleware('rate.limit:message.status,60,1');
    
    // Belirli mesajı getir - 30 istek/dakika
    Route::get('/{id}', [MessageController::class, 'show'])
        ->middleware('rate.limit:message.show,30,1');
});
