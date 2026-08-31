<?php

namespace App\Http\Controllers\Api\Courier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\CourierLoginRequest;
use App\Http\Resources\CourierResource;
use App\Models\Courier;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class CourierAuthController extends Controller
{
    public function login(CourierLoginRequest $request): JsonResponse
    {
        $courier = Courier::findByLoginPhone($request->validated('phone'));

        if ($courier === null || ! Hash::check($request->validated('password'), $courier->getAuthPassword())) {
            return ApiResponse::error('رقم الجوال أو كلمة المرور غير صحيحة.', 401);
        }

        if (! $courier->is_active) {
            return ApiResponse::error('حسابك غير مفعّل. تواصل مع الإدارة.', 403);
        }

        $courier->tokens()->where('name', 'courier')->delete();
        $token = $courier->createToken('courier')->plainTextToken;

        return ApiResponse::success('تم تسجيل الدخول بنجاح.', [
            'token' => $token,
            'token_type' => 'Bearer',
            'courier' => (new CourierResource($courier))->resolve(),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var Courier $courier */
        $courier = $request->user();

        return ApiResponse::success('بيانات الموصل.', [
            'courier' => (new CourierResource($courier))->resolve(),
        ]);
    }

    public function availability(Request $request): JsonResponse
    {
        /** @var Courier $courier */
        $courier = $request->user();
        $courier->update([
            'is_online' => $request->boolean('is_online'),
        ]);

        return ApiResponse::success(
            $courier->is_online ? 'أنت متاح لاستلام الطلبات.' : 'تم إيقاف استلام الطلبات.',
            ['courier' => (new CourierResource($courier->fresh()))->resolve()],
        );
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var Courier $courier */
        $courier = $request->user();
        $courier->update(['is_online' => false]);
        $courier->currentAccessToken()?->delete();

        return ApiResponse::success('تم تسجيل الخروج.');
    }

    public function account(Request $request): JsonResponse
    {
        /** @var Courier $courier */
        $courier = $request->user();
        $summary = app(\App\Services\Couriers\CourierLedgerService::class)->summary($courier);
        $entries = $courier->ledgerEntries()
            ->with('order:id,order_number')
            ->limit(40)
            ->get()
            ->map(fn ($entry) => [
                'id' => (string) $entry->id,
                'type' => $entry->type,
                'type_label' => $entry->typeLabel(),
                'direction' => $entry->direction,
                'amount' => (float) $entry->amount,
                'note' => $entry->note,
                'order_number' => $entry->order?->order_number,
                'created_at' => $entry->created_at?->toIso8601String(),
            ]);

        return ApiResponse::success('كشف حساب الموصل.', [
            'courier' => (new CourierResource($courier))->resolve(),
            'summary' => $summary,
            'entries' => $entries,
        ]);
    }
}
