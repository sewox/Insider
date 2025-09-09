<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\MessageService;
use App\Services\SmsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

/**
 * @OA\Info(
 *     title="Insider SMS API",
 *     version="1.0.0",
 *     description="Otomatik mesaj gönderim sistemi API dokümantasyonu"
 * )
 * 
 * @OA\Server(
 *     url="http://localhost",
 *     description="Development Server"
 * )
 * 
 * @OA\Tag(
 *     name="Messages",
 *     description="Mesaj yönetimi API endpoints"
 * )
 */
class MessageController extends Controller
{
    protected $messageService;
    protected $smsService;

    public function __construct(MessageService $messageService, SmsService $smsService)
    {
        $this->messageService = $messageService;
        $this->smsService = $smsService;
    }

    /**
     * @OA\Get(
     *     path="/api/messages",
     *     summary="Gönderilmiş mesajları listele",
     *     tags={"Messages"},
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="content", type="string", example="Test mesajı"),
     *                 @OA\Property(property="status", type="string", example="sent"),
     *                 @OA\Property(property="external_message_id", type="string", example="msg_123456"),
     *                 @OA\Property(property="sent_at", type="string", format="datetime"),
     *                 @OA\Property(property="created_at", type="string", format="datetime"),
     *                 @OA\Property(property="recipients", type="array", @OA\Items(
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="phone_number", type="string", example="+905551234567"),
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="status", type="string", example="sent"),
     *                     @OA\Property(property="external_message_id", type="string", example="msg_123456"),
     *                     @OA\Property(property="sent_at", type="string", format="datetime")
     *                 ))
     *             ))
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            $messages = $this->messageService->getSentMessages();
            
            return response()->json([
                'success' => true,
                'data' => $messages,
                'message' => 'Gönderilmiş mesajlar başarıyla getirildi.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mesajlar getirilirken hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/messages",
     *     summary="Yeni mesaj oluştur",
     *     tags={"Messages"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"content", "recipients"},
     *             @OA\Property(property="content", type="string", example="Test mesajı", maxLength=160),
     *             @OA\Property(property="recipients", type="array", @OA\Items(
     *                 @OA\Property(property="phone_number", type="string", example="+905551234567"),
     *                 @OA\Property(property="name", type="string", example="John Doe")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Mesaj başarıyla oluşturuldu",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object"),
     *             @OA\Property(property="message", type="string", example="Mesaj başarıyla oluşturuldu.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation hatası"
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'content' => 'required|string|max:160',
            'recipients' => 'required|array|min:1',
            'recipients.*.phone_number' => 'required|string|regex:/^\+?[1-9]\d{1,14}$/',
            'recipients.*.name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation hatası',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $message = $this->messageService->createMessageWithRecipients(
                $request->input('content'),
                $request->input('recipients')
            );

            return response()->json([
                'success' => true,
                'data' => $message,
                'message' => 'Mesaj başarıyla oluşturuldu.',
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mesaj oluşturulurken hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/messages/{id}",
     *     summary="Belirli bir mesajı getir",
     *     tags={"Messages"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer"),
     *         description="Mesaj ID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Mesaj bulunamadı"
     *     )
     * )
     */
    public function show(string $id): JsonResponse
    {
        try {
            $message = $this->messageService->getMessageById((int) $id);
            
            if (!$message) {
                return response()->json([
                    'success' => false,
                    'message' => 'Mesaj bulunamadı.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $message->load('recipients'),
                'message' => 'Mesaj başarıyla getirildi.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mesaj getirilirken hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/messages/sent/list",
     *     summary="Gönderilmiş mesajların ID listesini getir",
     *     tags={"Messages"},
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message_ids", type="array", @OA\Items(type="string", example="msg_123456")),
     *                 @OA\Property(property="count", type="integer", example=5)
     *             )
     *         )
     *     )
     * )
     */
    public function sentList(): JsonResponse
    {
        try {
            $messages = $this->messageService->getSentMessages();
            
            $messageIds = $messages->pluck('external_message_id')
                ->filter()
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'data' => [
                    'message_ids' => $messageIds,
                    'count' => count($messageIds),
                ],
                'message' => 'Gönderilmiş mesaj ID\'leri başarıyla getirildi.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mesaj ID\'leri getirilirken hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/messages/status/{messageId}",
     *     summary="Mesaj durumunu kontrol et (Redis cache'den)",
     *     tags={"Messages"},
     *     @OA\Parameter(
     *         name="messageId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string"),
     *         description="External Message ID"
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Başarılı",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function checkStatus(string $messageId): JsonResponse
    {
        try {
            $status = $this->smsService->checkMessageStatus($messageId);
            
            return response()->json([
                'success' => true,
                'data' => $status,
                'message' => 'Mesaj durumu başarıyla getirildi.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Mesaj durumu getirilirken hata oluştu: ' . $e->getMessage(),
            ], 500);
        }
    }
}
