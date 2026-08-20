<?php

namespace App\Services\Admin;

use App\Models\AdminEvent;
use App\Models\Courier;
use App\Models\CourierLedgerEntry;
use App\Models\Order;

class AdminEventService
{
    public function record(
        string $type,
        string $title,
        string $body,
        ?Order $order = null,
        ?Courier $courier = null,
        array $data = [],
    ): void {
        try {
            AdminEvent::query()->create([
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'order_id' => $order?->id,
                'courier_id' => $courier?->id,
                'data' => $data ?: null,
            ]);
        } catch (\Throwable) {
            // الإشعار الإداري لا يوقف العملية الأساسية
        }
    }

    public function orderPlaced(Order $order): void
    {
        $this->record(
            AdminEvent::TYPE_ORDER_PLACED,
            'طلب جديد',
            'وصل طلب '.$order->order_number.' بقيمة '.number_format((float) $order->total, 2),
            $order,
        );
    }

    public function courierAccepted(Order $order, Courier $courier): void
    {
        $this->record(
            AdminEvent::TYPE_COURIER_ACCEPTED,
            'الموصل قبل الطلب',
            $courier->name.' قبل الطلب '.$order->order_number,
            $order,
            $courier,
        );
    }

    public function courierPickedUp(Order $order, Courier $courier): void
    {
        $this->record(
            AdminEvent::TYPE_COURIER_PICKED_UP,
            'تم استلام الطلب من المتجر',
            $courier->name.' استلم الطلب '.$order->order_number.' وهو في الطريق',
            $order,
            $courier,
        );
    }

    public function courierDelivered(Order $order, Courier $courier): void
    {
        $this->record(
            AdminEvent::TYPE_COURIER_DELIVERED,
            'تم تسليم الطلب',
            $courier->name.' سلّم الطلب '.$order->order_number,
            $order,
            $courier,
        );
    }

    public function courierReassigned(Order $order, Courier $to, ?Courier $from = null): void
    {
        $this->record(
            AdminEvent::TYPE_COURIER_REASSIGNED,
            'تم تغيير الموصل',
            $from
                ? 'نُقل الطلب '.$order->order_number.' من '.$from->name.' إلى '.$to->name
                : 'عُيّن '.$to->name.' على الطلب '.$order->order_number,
            $order,
            $to,
        );
    }

    public function courierSettled(Courier $courier, float $amount): void
    {
        $this->record(
            AdminEvent::TYPE_COURIER_SETTLED,
            'تسديد موصل',
            'تم تسديد '.number_format($amount, 2).' من حساب '.$courier->name,
            null,
            $courier,
        );
    }

    /**
     * @return array{stamp: string|null, unread: int, latest_id: int, events: list<array<string, mixed>>, fresh: list<array<string, mixed>>}
     */
    public function snapshot(int $afterId = 0): array
    {
        $events = AdminEvent::query()->latest('id')->limit(12)->get();
        $fresh = $afterId > 0
            ? $events->filter(fn (AdminEvent $event) => $event->id > $afterId)->values()
            : collect();

        $stamp = collect([
            Order::query()->max('updated_at'),
            AdminEvent::query()->max('created_at'),
            CourierLedgerEntry::query()->max('created_at'),
        ])->filter()->max();

        return [
            'stamp' => $stamp ? (string) $stamp : null,
            'unread' => AdminEvent::query()->whereNull('read_at')->count(),
            'latest_id' => (int) ($events->max('id') ?? 0),
            'events' => $events->take(8)->map(fn (AdminEvent $event) => $event->toLiveArray())->values()->all(),
            'fresh' => $fresh->map(fn (AdminEvent $event) => $event->toLiveArray())->values()->all(),
        ];
    }

    public function markAllRead(): void
    {
        AdminEvent::query()->whereNull('read_at')->update(['read_at' => now()]);
    }
}
