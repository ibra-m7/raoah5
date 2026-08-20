<?php

namespace App\Services\Notifications;

use App\Enums\DevicePlatform;
use App\Enums\NotificationType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\AppNotification;
use App\Models\DeviceToken;
use App\Models\NotificationCampaign;
use App\Models\Order;
use App\Models\User;
use App\Support\AppStrings;

class NotificationService
{
    public function __construct(private readonly FcmClient $fcm) {}

    public function fcmReady(): bool
    {
        return $this->fcm->isConfigured();
    }

    public function eligibleCustomerCount(): int
    {
        return User::query()
            ->where('role', UserRole::Customer)
            ->where('notifications_enabled', true)
            ->count();
    }

    public function registerToken(User $user, string $token, DevicePlatform $platform): DeviceToken
    {
        return DeviceToken::query()->updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'last_used_at' => now(),
            ]
        );
    }

    public function unregisterToken(User $user, ?string $token): void
    {
        $query = DeviceToken::query()->where('user_id', $user->id);
        if (filled($token)) {
            $query->where('token', $token);
        }
        $query->delete();
    }

    public function setPreference(User $user, bool $enabled): User
    {
        $user->update(['notifications_enabled' => $enabled]);

        if (! $enabled) {
            DeviceToken::query()->where('user_id', $user->id)->delete();
        }

        return $user->fresh();
    }

    public function notifyOrder(Order $order): void
    {
        $order->loadMissing('user');
        $user = $order->user;
        if ($user === null) {
            return;
        }

        [$title, $body] = $this->orderCopy($order);

        $this->deliver(
            $user,
            $title,
            $body,
            NotificationType::Order,
            [
                'type' => NotificationType::Order->value,
                'order_id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
                'status' => $order->status?->value,
            ],
            push: (bool) $user->notifications_enabled,
        );
    }

    public function notifyOrderEdited(Order $order): void
    {
        $order->loadMissing('user');
        $user = $order->user;
        if ($user === null) {
            return;
        }

        $this->deliver(
            $user,
            'تم تعديل طلبك',
            'حدّثنا منتجات طلبك '.$order->order_number.' ليصبح الإجمالي '.number_format((float) $order->total, 2).' '.AppStrings::CURRENCY.'.',
            NotificationType::Order,
            [
                'type' => NotificationType::Order->value,
                'order_id' => (string) $order->id,
                'order_number' => (string) $order->order_number,
                'status' => $order->status?->value,
            ],
            push: (bool) $user->notifications_enabled,
        );
    }

    public function broadcast(
        string $title,
        string $body,
        NotificationType $type,
        ?User $author = null,
    ): NotificationCampaign {
        $campaign = NotificationCampaign::query()->create([
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'audience' => 'all_customers',
            'created_by' => $author?->id,
            'sent_at' => now(),
        ]);

        $users = User::query()
            ->where('role', UserRole::Customer)
            ->where('notifications_enabled', true)
            ->with('deviceTokens')
            ->get();

        $tokens = [];
        foreach ($users as $user) {
            AppNotification::query()->create([
                'user_id' => $user->id,
                'campaign_id' => $campaign->id,
                'title' => $title,
                'body' => $body,
                'type' => $type,
                'data' => [
                    'type' => $type->value,
                    'campaign_id' => (string) $campaign->id,
                ],
            ]);

            foreach ($user->deviceTokens as $device) {
                $tokens[] = $device->token;
            }
        }

        $pushCount = 0;
        if ($this->fcm->isConfigured() && $tokens !== []) {
            $invalid = $this->fcm->send($tokens, $title, $body, [
                'type' => $type->value,
                'campaign_id' => (string) $campaign->id,
            ]);
            $this->forgetTokens($invalid);
            $pushCount = max(0, count($tokens) - count($invalid));
        }

        $campaign->update([
            'recipients_count' => $users->count(),
            'push_count' => $pushCount,
        ]);

        return $campaign->fresh();
    }

    public function resend(NotificationCampaign $campaign, ?User $author = null): NotificationCampaign
    {
        return $this->broadcast(
            $campaign->title,
            $campaign->body,
            $campaign->type ?? NotificationType::Promo,
            $author,
        );
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function orderCopy(Order $order): array
    {
        $number = $order->order_number;

        return match ($order->status) {
            OrderStatus::Pending => ['تم استلام طلبك', 'استلمنا طلبك '.$number.' وجاري تأكيده.'],
            OrderStatus::Preparing => ['جاري تحضير طلبك', 'طلبك '.$number.' قيد التحضير الآن.'],
            OrderStatus::OnTheWay => ['طلبك في الطريق', 'مندوب التوصيل في الطريق بطلبك '.$number.'.'],
            OrderStatus::Delivered => ['تم توصيل طلبك', 'تم تسليم طلبك '.$number.' بنجاح. نتمنى أن ينال إعجابك.'],
            OrderStatus::Cancelled => ['تم إلغاء الطلب', 'تم إلغاء طلبك '.$number.'.'],
            default => ['تحديث على طلبك', 'تم تحديث حالة طلبك '.$number.'.'],
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function deliver(
        User $user,
        string $title,
        string $body,
        NotificationType $type,
        array $data,
        bool $push,
    ): void {
        $row = AppNotification::query()->create([
            'user_id' => $user->id,
            'title' => $title,
            'body' => $body,
            'type' => $type,
            'data' => $data,
        ]);

        $data['notification_id'] = (string) $row->id;

        if (! $push) {
            return;
        }

        $tokens = DeviceToken::query()
            ->where('user_id', $user->id)
            ->pluck('token')
            ->all();

        $invalid = $this->fcm->send($tokens, $title, $body, $data);
        $this->forgetTokens($invalid);
    }

    /**
     * @param  list<string>  $tokens
     */
    private function forgetTokens(array $tokens): void
    {
        if ($tokens === []) {
            return;
        }

        DeviceToken::query()->whereIn('token', $tokens)->delete();
    }
}
