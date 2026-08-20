<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\Admin\AdminEventService;
use App\Services\Coupons\CouponService;
use App\Services\Couriers\CourierLedgerService;
use App\Services\Delivery\DeliveryService;
use App\Services\Notifications\NotificationService;
use App\Support\StoreSettings;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly CouponService $coupons,
        private readonly NotificationService $notifications,
        private readonly DeliveryService $delivery,
        private readonly AdminEventService $adminEvents,
        private readonly CourierLedgerService $ledger,
    ) {}

    public function paginateForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $user->orders()
            ->with('items')
            ->latest()
            ->paginate($perPage);
    }

    public function findForUser(User $user, string $id): ?Order
    {
        return $user->orders()
            ->with('items')
            ->where(fn ($q) => $q->where('id', $id)->orWhere('order_number', $id))
            ->first();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function previewCoupon(User $user, array $payload): array
    {
        return DB::transaction(function () use ($user, $payload) {
            $lines = $this->buildLines($payload['items'] ?? []);
            $subtotal = round($lines->sum('line_total'), 2);
            $quote = $this->coupons->quote($user, $payload['coupon_code'] ?? null, $lines);
            $address = $this->resolveAddress($user, $payload['address_id'] ?? null);
            $delivery = $this->delivery->quote(
                $user,
                $address,
                $subtotal,
                $quote?->freeShipping ?? false,
            );
            $shipping = $delivery->fee;
            $hasFree = $delivery->isFree;
            $discount = $quote?->discount ?? 0.0;
            $total = round(max(0, $subtotal - $discount + $shipping), 2);

            if ($quote === null) {
                throw ValidationException::withMessages([
                    'coupon_code' => 'أدخل كود الخصم.',
                ]);
            }

            return $quote->toArray($subtotal, $shipping, $total) + [
                'has_free_shipping' => $hasFree,
                'delivery' => $delivery->toArray(),
            ];
        });
    }

    public function create(User $user, array $payload): Order
    {
        $order = DB::transaction(function () use ($user, $payload) {
            $lines = $this->buildLines($payload['items'] ?? []);
            $subtotal = round($lines->sum('line_total'), 2);
            $quote = $this->coupons->quote($user, $payload['coupon_code'] ?? null, $lines);

            $address = $this->resolveAddress($user, $payload['address_id'] ?? null);
            if ($address === null) {
                throw ValidationException::withMessages([
                    'address_id' => 'أضف عنوان توصيل داخل السعودية قبل إتمام الطلب.',
                ]);
            }

            $delivery = $this->delivery->quote(
                $user,
                $address,
                $subtotal,
                $quote?->freeShipping ?? false,
            );
            $shipping = $delivery->fee;
            $hasFree = $delivery->isFree;
            $discount = $quote?->discount ?? 0.0;
            $total = round(max(0, $subtotal - $discount + $shipping), 2);

            $methodSlug = (string) ($payload['payment_method'] ?? 'cash');
            $activeSlugs = StoreSettings::activePaymentSlugs();
            if ($activeSlugs !== [] && ! in_array($methodSlug, $activeSlugs, true)) {
                throw ValidationException::withMessages([
                    'payment_method' => 'طريقة الدفع غير متاحة حالياً.',
                ]);
            }
            if ($activeSlugs === []) {
                $methodSlug = PaymentMethod::tryFrom($methodSlug)?->value ?? PaymentMethod::Cash->value;
            }

            $fulfillment = ($payload['fulfillment_type'] ?? 'now') === 'scheduled' ? 'scheduled' : 'now';
            $scheduledAt = $fulfillment === 'scheduled' ? ($payload['scheduled_at'] ?? null) : null;
            $couponCode = $quote?->coupon->code;

            $order = Order::query()->create([
                'user_id' => $user->id,
                'address_id' => $address->id,
                'order_number' => $this->nextNumber(),
                'status' => OrderStatus::Pending,
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping,
                'total' => $total,
                'has_free_shipping' => $hasFree,
                'delivery_label' => $delivery->label,
                'shipping_manual' => false,
                'payment_method' => $methodSlug,
                'payment_status' => PaymentStatus::Pending,
                'shipping_name' => $payload['shipping_name'] ?? $address->recipient_name ?? $user->name,
                'shipping_phone' => $payload['shipping_phone'] ?? $address->phone ?? $user->phone ?? '',
                'shipping_city' => $payload['shipping_city'] ?? $address->city ?? 'السعودية',
                'shipping_district' => $payload['shipping_district'] ?? $address->district,
                'shipping_street' => $payload['shipping_street'] ?? $address->street,
                'shipping_details' => $payload['shipping_details'] ?? $address->details,
                'notes' => $payload['notes'] ?? null,
                'coupon_id' => $quote?->coupon->id,
                'coupon_code' => $couponCode,
                'discount_amount' => $discount,
                'fulfillment_type' => $fulfillment,
                'scheduled_at' => $scheduledAt,
            ]);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image' => $product->primaryImage?->url,
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
                $product->decrement('stock', $line['quantity']);
            }

            if ($quote !== null) {
                $this->coupons->redeem($quote->coupon, $user, $order->id, $discount);
            }

            $order->statusHistories()->create([
                'status' => OrderStatus::Pending,
                'note' => 'تم إنشاء الطلب — '.$order->paymentMethodLabel(),
            ]);

            return $order->load('items');
        });

        $this->notifyQuietly($order);
        $this->adminEvents->orderPlaced($order);

        return $order;
    }

    public function cancelForUser(User $user, string $id, ?string $reason = null): Order
    {
        $order = DB::transaction(function () use ($user, $id, $reason) {
            $order = $this->findForUser($user, $id);
            if ($order === null) {
                throw ValidationException::withMessages([
                    'order' => 'الطلب غير موجود.',
                ]);
            }

            if (! $order->canBeCancelledByCustomer()) {
                throw ValidationException::withMessages([
                    'order' => 'لا يمكن إلغاء الطلب بعد بدء التوصيل.',
                ]);
            }

            return $this->markCancelled($order, 'customer', $reason ?: 'ألغاه العميل');
        });

        $this->notifyQuietly($order);

        return $order;
    }

    /**
     * @param  list<array{product_id: mixed, quantity: mixed}>  $rawItems
     */
    public function updateItems(Order $order, array $rawItems, ?string $notes = null, ?float $manualShipping = null, bool $recalcShipping = false): Order
    {
        $updated = DB::transaction(function () use ($order, $rawItems, $notes) {
            $order = Order::query()->with(['items', 'user', 'address'])->lockForUpdate()->findOrFail($order->id);

            if ($order->status === OrderStatus::Cancelled) {
                throw ValidationException::withMessages([
                    'order' => 'لا يمكن تعديل طلب ملغي.',
                ]);
            }

            $existingIds = $order->items
                ->pluck('product_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->all();

            foreach ($order->items as $item) {
                if ($item->product_id) {
                    Product::query()->whereKey($item->product_id)->increment('stock', $item->quantity);
                }
            }

            $order->items()->delete();

            $lines = $this->buildLines($rawItems, $existingIds);

            foreach ($lines as $line) {
                /** @var Product $product */
                $product = $line['product'];
                $order->items()->create([
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'product_image' => $product->primaryImage?->url,
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
                $product->decrement('stock', $line['quantity']);
            }

            $subtotal = round($lines->sum('line_total'), 2);
            $quote = null;
            $couponCode = $order->coupon_code;
            if (filled($couponCode) && $order->user) {
                try {
                    $quote = $this->coupons->quote($order->user, $couponCode, $lines, $order->id);
                } catch (ValidationException) {
                    $quote = null;
                    $couponCode = null;
                }
            }

            $this->coupons->releaseForOrder($order->id);
            $postedFee = $manualShipping;
            $feeChanged = $postedFee !== null && abs($postedFee - (float) $order->shipping_fee) > 0.001;
            $useManual = ! $recalcShipping && ($order->shipping_manual || $feeChanged);
            if ($useManual) {
                $shipping = round(max(0, $postedFee ?? (float) $order->shipping_fee), 2);
                $hasFree = $shipping <= 0;
                $deliveryLabel = $order->delivery_label ?: 'رسوم معدّلة يدوياً';
                $shippingManual = true;
            } else {
                $delivery = $this->delivery->quote(
                    $order->user,
                    $order->address,
                    $subtotal,
                    $quote?->freeShipping ?? false,
                    $order->id,
                );
                $shipping = $delivery->fee;
                $hasFree = $delivery->isFree;
                $deliveryLabel = $delivery->label;
                $shippingManual = false;
            }
            $discount = $quote?->discount ?? 0.0;
            $total = round(max(0, $subtotal - $discount + $shipping), 2);

            if ($quote !== null) {
                $this->coupons->redeem($quote->coupon, $order->user, $order->id, $discount);
                $couponCode = $quote->coupon->code;
            }

            $order->update([
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping,
                'total' => $total,
                'has_free_shipping' => $hasFree,
                'shipping_manual' => $shippingManual,
                'delivery_label' => $deliveryLabel,
                'discount_amount' => $discount,
                'coupon_id' => $quote?->coupon->id,
                'coupon_code' => $couponCode,
                'notes' => $notes,
            ]);

            $order->statusHistories()->create([
                'status' => $order->status,
                'note' => 'تم تعديل منتجات الطلب من لوحة التحكم',
            ]);

            return $order->fresh(['user', 'items']);
        });

        $this->notifyItemsChanged($updated);

        return $updated;
    }

    public function updateStatus(
        Order $order,
        OrderStatus $status,
        ?PaymentStatus $paymentStatus = null,
        ?string $note = null,
    ): Order {
        $previous = $order->status;
        $historyNote = $note ?: 'تم تحديث الحالة من لوحة التحكم';

        $updated = DB::transaction(function () use ($order, $status, $paymentStatus, $historyNote) {
            $order = Order::query()->with('items')->lockForUpdate()->findOrFail($order->id);
            $wasCancelled = $order->status === OrderStatus::Cancelled;

            if ($status === OrderStatus::Cancelled && ! $wasCancelled) {
                return $this->markCancelled($order, 'admin', $historyNote, $paymentStatus);
            }

            $order->update(['status' => $status]);

            if ($paymentStatus !== null) {
                $order->update(['payment_status' => $paymentStatus]);
            } elseif ($status === OrderStatus::Delivered && $order->paymentMethodSlug() === PaymentMethod::Cash->value) {
                $order->update(['payment_status' => PaymentStatus::Paid]);
            }

            $order->statusHistories()->create([
                'status' => $status,
                'note' => $historyNote,
            ]);

            $fresh = $order->fresh(['user', 'items', 'courier']);
            if ($status === OrderStatus::Delivered) {
                $this->ledger->recordCod($fresh);
            }

            return $fresh;
        });

        if ($previous !== $updated->status) {
            $this->notifyQuietly($updated);
        }

        return $updated;
    }

    public function assignCourier(Order $order, Courier $courier): Order
    {
        $from = $order->courier;

        $updated = DB::transaction(function () use ($order, $courier) {
            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! in_array($locked->status, [OrderStatus::Pending, OrderStatus::Preparing, OrderStatus::OnTheWay], true)) {
                throw ValidationException::withMessages([
                    'courier_id' => 'لا يمكن تغيير الموصل بعد تسليم الطلب أو إلغائه.',
                ]);
            }

            if ((int) $locked->courier_id === (int) $courier->id) {
                return $locked->fresh(['user', 'items', 'courier']);
            }

            if (! $courier->is_active) {
                throw ValidationException::withMessages([
                    'courier_id' => 'هذا الموصل غير مفعّل.',
                ]);
            }

            $hadCourier = $locked->courier_id !== null;
            $payload = ['courier_id' => $courier->id];
            if ($locked->status === OrderStatus::Pending) {
                $payload['status'] = OrderStatus::Preparing;
            }

            $locked->update($payload);

            $locked->statusHistories()->create([
                'status' => $locked->status,
                'note' => $hadCourier
                    ? 'تم تحويل الطلب من الإدارة إلى الموصل '.$courier->name
                    : 'تم تعيين الموصل '.$courier->name.' من الإدارة',
            ]);

            return $locked->fresh(['user', 'items', 'courier']);
        });

        if ((int) $order->courier_id !== (int) $updated->courier_id) {
            $this->adminEvents->courierReassigned($updated, $courier, $from);
        }

        if ($order->status !== $updated->status) {
            $this->notifyQuietly($updated);
        }

        return $updated;
    }

    public function acceptByCourier(Order $order, Courier $courier): Order
    {
        $updated = DB::transaction(function () use ($order, $courier) {
            if (! $courier->canReceiveOrders()) {
                throw ValidationException::withMessages([
                    'order' => 'فعّل حالة التواجد (متاح) لاستلام الطلبات.',
                ]);
            }

            $locked = Order::query()->lockForUpdate()->findOrFail($order->id);
            $acceptable = in_array($locked->status, [OrderStatus::Pending, OrderStatus::Preparing], true);

            if (! $acceptable) {
                throw ValidationException::withMessages([
                    'order' => 'لا يمكن قبول هذا الطلب الآن.',
                ]);
            }

            if ($locked->courier_id !== null) {
                throw ValidationException::withMessages([
                    'order' => 'تم قبول هذا الطلب من موصل آخر.',
                ]);
            }

            $locked->update([
                'courier_id' => $courier->id,
                'status' => OrderStatus::Preparing,
            ]);
            $locked->statusHistories()->create([
                'status' => OrderStatus::Preparing,
                'note' => 'قبل الموصل الطلب: '.$courier->name,
            ]);

            return $locked->fresh(['items', 'address', 'courier', 'user']);
        });

        $this->notifyQuietly($updated);
        $this->adminEvents->courierAccepted($updated, $courier);

        return $updated;
    }

    public function pickupByCourier(Order $order, Courier $courier): Order
    {
        if ((int) $order->courier_id !== (int) $courier->id) {
            throw ValidationException::withMessages([
                'order' => 'هذا الطلب غير معيّن لك.',
            ]);
        }

        if ($order->status !== OrderStatus::Preparing) {
            throw ValidationException::withMessages([
                'order' => 'استلم الطلب من المتجر بعد قبول التحضير.',
            ]);
        }

        $updated = $this->updateStatus($order, OrderStatus::OnTheWay, null, 'استلم الموصل الطلب من المتجر');
        $this->adminEvents->courierPickedUp($updated, $courier);

        return $updated;
    }

    public function deliverByCourier(Order $order, Courier $courier): Order
    {
        if ((int) $order->courier_id !== (int) $courier->id) {
            throw ValidationException::withMessages([
                'order' => 'هذا الطلب غير معيّن لك.',
            ]);
        }

        if ($order->status !== OrderStatus::OnTheWay) {
            throw ValidationException::withMessages([
                'order' => 'أكّد الاستلام من المتجر قبل تسليم الطلب.',
            ]);
        }

        $updated = $this->updateStatus($order, OrderStatus::Delivered, null, 'أكد الموصل التسليم');
        $this->adminEvents->courierDelivered($updated, $courier);

        return $updated;
    }

    private function markCancelled(
        Order $order,
        string $by,
        string $note,
        ?PaymentStatus $paymentStatus = null,
    ): Order {
        $order = Order::query()->with('items')->lockForUpdate()->findOrFail($order->id);
        if ($order->status === OrderStatus::Cancelled) {
            return $order->fresh(['items']);
        }

        foreach ($order->items as $item) {
            if ($item->product_id) {
                Product::query()->whereKey($item->product_id)->increment('stock', $item->quantity);
            }
        }

        $this->coupons->releaseForOrder($order->id);

        $nextPayment = $paymentStatus;
        if ($nextPayment === null) {
            $nextPayment = $order->payment_status === PaymentStatus::Paid
                ? PaymentStatus::Refunded
                : $order->payment_status;
        }

        $order->update([
            'status' => OrderStatus::Cancelled,
            'payment_status' => $nextPayment,
            'cancelled_by' => $by,
            'cancel_reason' => $note,
            'cancelled_at' => now(),
        ]);

        $order->statusHistories()->create([
            'status' => OrderStatus::Cancelled,
            'note' => $note,
        ]);

        return $order->fresh(['items']);
    }

    /**
     * @param  list<array{product_id: mixed, quantity: mixed}>  $rawItems
     * @param  list<int>  $allowInactiveIds
     * @return Collection<int, array{product: Product, quantity: int, unit_price: float, line_total: float}>
     */
    public function buildLines(array $rawItems, array $allowInactiveIds = []): Collection
    {
        $quantities = collect($rawItems)
            ->groupBy(fn ($item) => (int) ($item['product_id'] ?? 0))
            ->map(fn ($group) => $group->sum(fn ($item) => (int) ($item['quantity'] ?? 0)))
            ->reject(fn ($qty, $id) => (int) $id < 1 || $qty < 1);

        if ($quantities->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'أضف منتجاً واحداً على الأقل.',
            ]);
        }

        $products = Product::query()
            ->with('primaryImage')
            ->whereIn('id', $quantities->keys())
            ->when(
                $allowInactiveIds === [],
                fn ($query) => $query->active(),
                fn ($query) => $query->where(function ($nested) use ($allowInactiveIds) {
                    $nested->active()->orWhereIn('id', $allowInactiveIds);
                }),
            )
            ->get()
            ->keyBy('id');

        if ($products->count() !== $quantities->count()) {
            throw ValidationException::withMessages([
                'items' => 'أحد المنتجات غير متاح حالياً.',
            ]);
        }

        $lines = collect();
        foreach ($quantities as $productId => $qty) {
            /** @var Product $product */
            $product = $products->get($productId);
            if ($qty < 1) {
                throw ValidationException::withMessages([
                    'items' => 'الكمية يجب أن تكون 1 على الأقل.',
                ]);
            }
            if ($product->stock < $qty) {
                throw ValidationException::withMessages([
                    'items' => "الكمية غير متاحة للمنتج {$product->name}.",
                ]);
            }

            $unit = (float) $product->effective_price;
            $lineTotal = round($unit * $qty, 2);
            $lines->push([
                'product' => $product,
                'quantity' => $qty,
                'unit_price' => $unit,
                'line_total' => $lineTotal,
            ]);
        }

        return $lines;
    }

    private function resolveAddress(User $user, mixed $addressId): ?\App\Models\Address
    {
        $id = is_numeric($addressId) ? (int) $addressId : null;
        $address = $id
            ? $user->addresses()->whereKey($id)->first()
            : null;

        return $address ?? $user->addresses()->orderByDesc('is_default')->first();
    }

    private function nextNumber(): string
    {
        $last = (int) (Order::query()
            ->whereRaw("order_number REGEXP '^[0-9]+$'")
            ->selectRaw('MAX(CAST(order_number AS UNSIGNED)) as seq')
            ->value('seq') ?? 0);

        return (string) ($last + 1);
    }

    private function notifyItemsChanged(Order $order): void
    {
        try {
            $this->notifications->notifyOrderEdited($order);
        } catch (\Throwable) {
            //
        }
    }

    private function notifyQuietly(Order $order): void
    {
        try {
            $this->notifications->notifyOrder($order);
        } catch (\Throwable) {
            // إنشاء الطلب لا يفشل إذا تعذّر الإشعار
        }
    }
}
