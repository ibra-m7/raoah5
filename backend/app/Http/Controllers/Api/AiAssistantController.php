<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\AiAssistantException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\AiChatRequest;
use App\Services\Ai\AiAssistantService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AiAssistantController extends Controller
{
    public function __construct(private readonly AiAssistantService $assistant) {}

    public function config(): JsonResponse
    {
        return ApiResponse::success('إعدادات المساعد', $this->assistant->config());
    }

    public function chat(AiChatRequest $request): JsonResponse
    {
        try {
            $data = $this->assistant->chat(
                $request->validated('message'),
                Auth::guard('sanctum')->user(),
                $request->validated('guest_token'),
                $request->validated('conversation_id') !== null
                    ? (int) $request->validated('conversation_id')
                    : null,
                $request->validated('intent') ?: 'chat',
                $request->validated('product_id'),
            );
        } catch (AiAssistantException $e) {
            return ApiResponse::error($e->getMessage(), $e->status);
        }

        return ApiResponse::success('رد المساعد', $data);
    }
}
