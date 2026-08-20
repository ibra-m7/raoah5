<?php

namespace App\Http\Controllers\Api;

use App\Enums\DevicePlatform;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterDeviceTokenRequest;
use App\Http\Resources\NotificationResource;
use App\Models\AppNotification;
use App\Services\Addresses\AddressService;
use App\Services\Notifications\NotificationService;
use App\Support\ApiResponse;
use App\Support\Constants;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $page = $user->appNotifications()
            ->latest('id')
            ->paginate(Constants::DEFAULT_PAGE_SIZE);

        $unread = $user->appNotifications()->unread()->count();

        return response()->json([
            'success' => true,
            'message' => 'الإشعارات',
            'data' => NotificationResource::collection($page->getCollection())->resolve(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
                'unread_count' => $unread,
            ],
        ]);
    }

    public function markRead(Request $request, AppNotification $notification): JsonResponse
    {
        if ($notification->user_id !== $request->user()->id) {
            return ApiResponse::error('الإشعار غير موجود.', 404);
        }

        if ($notification->read_at === null) {
            $notification->update(['read_at' => now()]);
        }

        return ApiResponse::success('تم تعليم الإشعار كمقروء.', (new NotificationResource($notification->fresh()))->resolve());
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->appNotifications()->unread()->update(['read_at' => now()]);

        return ApiResponse::success('تم تعليم كل الإشعارات كمقروءة.');
    }

    public function registerToken(RegisterDeviceTokenRequest $request): JsonResponse
    {
        $this->notifications->registerToken(
            $request->user(),
            $request->validated('token'),
            DevicePlatform::from($request->validated('platform')),
        );

        return ApiResponse::success('تم تفعيل إشعارات الجهاز.');
    }

    public function unregisterToken(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['nullable', 'string', 'max:4096'],
        ]);

        $this->notifications->unregisterToken(
            $request->user(),
            $request->input('token') ?? $request->query('token'),
        );

        return ApiResponse::success('تم إلغاء تسجيل الجهاز.');
    }

    public function updatePreference(Request $request, AddressService $addresses): JsonResponse
    {
        $request->validate([
            'enabled' => ['required', 'boolean'],
        ]);

        $user = $this->notifications->setPreference($request->user(), $request->boolean('enabled'));

        return ApiResponse::success(
            $user->notifications_enabled ? 'تم تفعيل الإشعارات.' : 'تم إيقاف الإشعارات.',
            ['user' => $addresses->userPayload($user)],
        );
    }
}
