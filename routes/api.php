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

// Mesaj API Routes
Route::prefix('messages')->group(function () {
    Route::get('/', [MessageController::class, 'index']); // Gönderilmiş mesajları listele
    Route::post('/', [MessageController::class, 'store']); // Yeni mesaj oluştur
    Route::get('/sent/list', [MessageController::class, 'sentList']); // Gönderilmiş mesaj ID'lerini listele
    Route::get('/status/{messageId}', [MessageController::class, 'checkStatus']); // Mesaj durumunu kontrol et
    Route::get('/{id}', [MessageController::class, 'show']); // Belirli mesajı getir
});
