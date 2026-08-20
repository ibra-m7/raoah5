<?php

namespace App\Http\Controllers\Admin;

use App\Enums\NotificationType;
use App\Http\Controllers\Controller;
use App\Models\AppNotification;
use App\Models\NotificationCampaign;
use App\Services\Notifications\NotificationService;
use App\Support\AppStrings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notifications) {}

    public function index(): View
    {
        return view('admin.notifications.index', [
            'title' => AppStrings::NAV_NOTIFICATIONS,
            'fcmReady' => $this->notifications->fcmReady(),
            'eligibleCount' => $this->notifications->eligibleCustomerCount(),
            'types' => [NotificationType::Promo, NotificationType::General],
            'campaigns' => NotificationCampaign::query()
                ->with('author')
                ->latest('id')
                ->paginate(5, ['*'], 'campaigns')
                ->withQueryString(),
            'notifications' => AppNotification::query()
                ->with('user')
                ->latest('id')
                ->paginate(5, ['*'], 'inbox')
                ->withQueryString(),
        ]);
    }

    public function clearLog(): RedirectResponse
    {
        AppNotification::query()->delete();

        return redirect()
            ->route('admin.notifications.index')
            ->with('success', AppStrings::NOTIFICATION_LOG_CLEARED);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'body' => ['required', 'string', 'max:500'],
            'type' => ['required', Rule::enum(NotificationType::class)],
        ]);

        $campaign = $this->notifications->broadcast(
            $data['title'],
            $data['body'],
            NotificationType::from($data['type']),
            $request->user(),
        );

        $pushNote = $campaign->push_count > 0
            ? ' ووصل الإشعار إلى '.$campaign->push_count.' جهاز.'
            : ($this->notifications->fcmReady()
                ? ' ولم يُرسل إلى الأجهزة لأن العملاء لم يسجّلوا أجهزتهم بعد.'
                : ' وأُضيف إلى صندوق التطبيق. أضف حساب خدمة Firebase ليصل إلى شاشة القفل.');

        return back()->with(
            'success',
            'تم إرسال الإشعار إلى '.$campaign->recipients_count.' عميل.'.$pushNote
        );
    }

    public function resend(NotificationCampaign $campaign): RedirectResponse
    {
        $sent = $this->notifications->resend($campaign, request()->user());

        $pushNote = $sent->push_count > 0
            ? ' ووصل إلى '.$sent->push_count.' جهاز.'
            : '';

        return back()->with(
            'success',
            'أُعيد إرسال «'.$campaign->title.'» إلى '.$sent->recipients_count.' عميل.'.$pushNote
        );
    }
}
