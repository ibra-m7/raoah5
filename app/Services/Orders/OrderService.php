<?php

namespace App\Services\Orders;

use App\Enums\OrderMethod;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductBundle;
use App\Models\User;
use App\Services\Admin\AdminEventService;
use App\Services\Coupons\CouponQuote;
use App\Services\Coupons\CouponService;
use App\Services\Couriers\CourierLedgerService;
use App\Services\Delivery\DeliveryService;
use App\Services\Notifications\NotificationService;
use App\Services\Pickup\PickupSlotService;
use App\Support\DeliverySettings;
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
        private readonly PickupSlotService $pickupSlots,
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
            $lines = $this->buildLines(
                $payload['items'] ?? [],
                [],
                $payload['bundles'] ?? [],
            );
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
            $lines = $this->buildLines(
                $payload['items'] ?? [],
                [],
                $payload['bundles'] ?? [],
            );
            $subtotal = round($lines->sum('line_total'), 2);
            $quotes = $this->resolveCouponQuotes($user, $payload, $lines);
            $freeShippingFromCoupons = $quotes->contains(fn ($quote) => $quote->freeShipping);
            $discount = round(min($subtotal, $quotes->sum(fn ($quote) => $quote->discount)), 2);
            $primaryQuote = $quotes->first();

            $orderMethod = ($payload['order_method'] ?? 'delivery') === 'pickup'
                ? OrderMethod::Pickup
                : OrderMethod::Delivery;

            if ($orderMethod === OrderMethod::Pickup && ! DeliverySettings::pickupEnabled()) {
                throw ValidationException::withMessages([
                    'order_method' => 'الاستلام من المركز غير متاح حالياً.',
                ]);
            }

            $address = null;
            $shipping = 0.0;
            $hasFree = true;
            $deliveryLabel = null;

            if ($orderMethod === OrderMethod::Delivery) {
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
                    $freeShippingFromCoupons,
                );
                $shipping = $delivery->fee;
                $hasFree = $delivery->isFree;
                $deliveryLabel = $delivery->label;
            }

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

            if ($fulfillment === 'scheduled' && $scheduledAt === null) {
                throw ValidationException::withMessages([
                    'scheduled_at' => 'اختر وقت تنفيذ الطلب.',
                ]);
            }

            if ($orderMethod === OrderMethod::Pickup && $fulfillment === 'scheduled' && $scheduledAt !== null) {
                if (! $this->pickupSlots->isValidScheduledAt($scheduledAt)) {
                    throw ValidationException::withMessages([
                        'scheduled_at' => 'وقت التجهيز المحدد غير متاح.',
                    ]);
                }
            }

            $couponCode = $quotes->isEmpty()
                ? null
                : $quotes->map(fn ($quote) => $quote->coupon->code)->implode(', ');

            $storeAddress = DeliverySettings::storeAddress();

            $order = Order::query()->create([
                'user_id' => $user->id,
                'address_id' => $address?->id,
                'order_number' => $this->nextNumber(),
                'status' => OrderStatus::Pending,
                'order_method' => $orderMethod,
                'subtotal' => $subtotal,
                'shipping_fee' => $shipping,
                'total' => $total,
                'has_free_shipping' => $hasFree,
                'delivery_label' => $deliveryLabel,
                'shipping_manual' => false,
                'payment_method' => $methodSlug,
                'payment_status' => PaymentStatus::Pending,
                'shipping_name' => $orderMethod === OrderMethod::Pickup
                    ? ($user->name ?? 'عميل')
                    : ($payload['shipping_name'] ?? $address->recipient_name ?? $user->name),
                'shipping_phone' => $orderMethod === OrderMethod::Pickup
                    ? ($user->phone ?? '')
                    : ($payload['shipping_phone'] ?? $address->phone ?? $user->phone ?? ''),
                'shipping_city' => $orderMethod === OrderMethod::Pickup
                    ? 'استلام من المركز'
                    : ($payload['shipping_city'] ?? $address->city ?? 'السعودية'),
                'shipping_district' => $orderMethod === OrderMethod::Pickup
                    ? null
                    : ($payload['shipping_district'] ?? $address->district),
                'shipping_street' => $orderMethod === OrderMethod::Pickup
                    ? $storeAddress
                    : ($payload['shipping_street'] ?? $address->street),
                'shipping_details' => $orderMethod === OrderMethod::Pickup
                    ? $storeAddress
                    : ($payload['shipping_details'] ?? $address->details),
                'notes' => $payload['notes'] ?? null,
                'coupon_id' => $primaryQuote?->coupon->id,
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
                    'is_gift' => (bool) ($line['is_gift'] ?? false),
                    'gift_for_product_id' => $line['gift_for_product_id'] ?? null,
                ]);
                $product->decrement('stock', $line['quantity']);
            }

            foreach ($quotes as $quote) {
                $this->coupons->redeem($quote->coupon, $user, $order->id, $quote->discount);
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
                    'is_gift' => (bool) ($line['is_gift'] ?? false),
                    'gift_for_product_id' => $line['gift_for_product_id'] ?? null,
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

            $orderMethod = $locked->order_method?->value ?? OrderMethod::Delivery->value;
            if (! $courier->handlesOrderMethod($orderMethod)) {
                throw ValidationException::withMessages([
                    'order' => 'هذا النوع من الطلبات غير مخصص لك.',
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
                'order' => $order->isPickup()
                    ? 'أكّد جاهزية الطلب للاستلام أولاً.'
                    : 'استلم الطلب من المتجر بعد قبول التحضير.',
            ]);
        }

        $note = $order->isPickup()
            ? 'الطلب جاهز لاستلام العميل من المركز'
            : 'استلم الموصل الطلب من المتجر';

        $updated = $this->updateStatus($order, OrderStatus::OnTheWay, null, $note);
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
                'order' => $order->isPickup()
                    ? 'أكّد جاهزية الطلب قبل تسليمه للعميل.'
                    : 'أكّد الاستلام من المتجر قبل تسليم الطلب.',
            ]);
        }

        $note = $order->isPickup()
            ? 'استلم العميل الطلب من المركز'
            : 'أكد الموصل التسليم';

        $updated = $this->updateStatus($order, OrderStatus::Delivered, null, $note);
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
     * @param  list<array{bundle_id: mixed, quantity: mixed}>  $rawBundles
     * @return Collection<int, array{product: Product, quantity: int, unit_price: float, line_total: float, is_gift?: bool}>
     */
    public function buildLines(
        array $rawItems,
        array $allowInactiveIds = [],
        array $rawBundles = [],
    ): Collection {
        $quantities = collect($rawItems)
            ->groupBy(fn ($item) => (int) ($item['product_id'] ?? 0))
            ->map(fn ($group) => $group->sum(fn ($item) => (int) ($item['quantity'] ?? 0)))
            ->reject(fn ($qty, $id) => (int) $id < 1 || $qty < 1);

        $bundleRequests = collect($rawBundles)
            ->map(fn ($row) => [
                'bundle_id' => (int) ($row['bundle_id'] ?? 0),
                'quantity' => max(1, (int) ($row['quantity'] ?? 0)),
            ])
            ->filter(fn ($row) => $row['bundle_id'] > 0)
            ->groupBy('bundle_id')
            ->map(fn ($group) => $group->sum('quantity'));

        if ($quantities->isEmpty() && $bundleRequests->isEmpty()) {
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
                'is_gift' => false,
            ]);
        }

        $lines = $this->appendBundleLines($lines, $bundleRequests, $allowInactiveIds);

        return $this->appendGiftLines($lines);
    }

    /**
     * @param  Collection<int, array{product: Product, quantity: int, unit_price: float, line_total: float, is_gift?: bool}>  $lines
     * @param  Collection<int, int>  $bundleRequests
     * @param  list<int>  $allowInactiveIds
     * @return Collection<int, array{product: Product, quantity: int, unit_price: float, line_total: float, is_gift?: bool}>
     */
    private function appendBundleLines(
        Collection $lines,
        Collection $bundleRequests,
        array $allowInactiveIds = [],
    ): Collection {
        if ($bundleRequests->isEmpty()) {
            return $lines;
        }

        $bundles = ProductBundle::query()
            ->with(['items.product.primaryImage'])
            ->whereIn('id', $bundleRequests->keys())
            ->when(
                $allowInactiveIds === [],
                fn ($query) => $query->where('is_active', true),
            )
            ->get()
            ->keyBy('id');

        if ($bundles->count() !== $bundleRequests->count()) {
            throw ValidationException::withMessages([
                'bundles' => 'إحدى السلات غير متاحة حالياً.',
            ]);
        }

        foreach ($bundleRequests as $bundleId => $bundleQty) {
            /** @var ProductBundle $bundle */
            $bundle = $bundles->get((int) $bundleId);
            if ($bundle === null || $bundle->items->isEmpty()) {
                throw ValidationException::withMessages([
                    'bundles' => 'إحدى السلات غير متاحة حالياً.',
                ]);
            }

            if (! $bundle->computeIsAvailable()) {
                throw ValidationException::withMessages([
                    'bundles' => "السلة {$bundle->name} غير متوفرة حالياً.",
                ]);
            }

            foreach ($bundle->items as $bundleItem) {
                $product = $bundleItem->product;
                $needed = max(1, (int) $bundleItem->quantity) * (int) $bundleQty;
                if ($product === null || (int) $product->stock < $needed) {
                    throw ValidationException::withMessages([
                        'bundles' => "الكمية غير متاحة لسلة {$bundle->name}.",
                    ]);
                }
            }

            $components = [];
            $baseTotal = 0.0;
            foreach ($bundle->items as $bundleItem) {
                $product = $bundleItem->product;
                if ($product === null) {
                    continue;
                }
                $qty = max(1, (int) $bundleItem->quantity) * (int) $bundleQty;
                $base = (float) $product->effective_price * $qty;
                $baseTotal += $base;
                $components[] = [
                    'product' => $product,
                    'quantity' => $qty,
                    'base_total' => $base,
                ];
            }

            if ($components === []) {
                throw ValidationException::withMessages([
                    'bundles' => "السلة {$bundle->name} فارغة.",
                ]);
            }

            $targetTotal = round((float) $bundle->bundle_price * (int) $bundleQty, 2);
            $allocated = 0.0;
            foreach ($components as $index => $component) {
                $isLast = $index === count($components) - 1;
                $lineTotal = $isLast
                    ? round($targetTotal - $allocated, 2)
                    : ($baseTotal > 0
                        ? round($targetTotal * ($component['base_total'] / $baseTotal), 2)
                        : 0.0);
                $allocated += $lineTotal;
                $qty = (int) $component['quantity'];
                $unit = $qty > 0 ? round($lineTotal / $qty, 2) : 0.0;

                $lines->push([
                    'product' => $component['product'],
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'line_total' => $lineTotal,
                    'is_gift' => false,
                ]);
            }
        }

        return $lines;
    }

    /**
     * @param  Collection<int, array{product: Product, quantity: int, unit_price: float, line_total: float, is_gift?: bool, gift_for_product_id?: int|null}>  $lines
     * @return Collection<int, array{product: Product, quantity: int, unit_price: float, line_total: float, is_gift?: bool, gift_for_product_id?: int|null}>
     */
    private function appendGiftLines(Collection $lines): Collection
    {
        $paidLines = $lines->filter(fn (array $line) => ! ($line['is_gift'] ?? false));
        if ($paidLines->isEmpty()) {
            return $lines;
        }

        $parents = Product::query()
            ->with([
                'giftProducts' => fn ($query) => $query
                    ->active()
                    ->where('stock', '>', 0)
                    ->with('primaryImage'),
            ])
            ->whereIn('id', $paidLines->pluck('product')->map(fn (Product $product) => $product->id))
            ->get()
            ->keyBy('id');

        $giftLines = collect();
        $giftStockUsed = [];

        foreach ($paidLines as $line) {
            /** @var Product $parent */
            $parent = $line['product'];
            $parentModel = $parents->get($parent->id);
            if ($parentModel === null) {
                continue;
            }

            $gift = $parentModel->giftProducts->first();
            if ($gift === null) {
                continue;
            }

            $giftId = (int) $gift->id;
            $used = $giftStockUsed[$giftId] ?? 0;
            $available = max(0, (int) $gift->stock - $used);
            $qty = min((int) $line['quantity'], $available);
            if ($qty < 1) {
                continue;
            }

            $giftStockUsed[$giftId] = $used + $qty;

            $giftLines->push([
                'product' => $gift,
                'quantity' => $qty,
                'unit_price' => 0.0,
                'line_total' => 0.0,
                'is_gift' => true,
                'gift_for_product_id' => (int) $parent->id,
            ]);
        }

        return $lines->concat($giftLines->values());
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  Collection<int, array{product: Product, quantity: int, unit_price: float, line_total: float, is_gift?: bool}>  $lines
     * @return Collection<int, CouponQuote>
     */
    private function resolveCouponQuotes(User $user, array $payload, Collection $lines): Collection
    {
        $codes = [];
        if (! empty($payload['coupon_codes']) && is_array($payload['coupon_codes'])) {
            foreach ($payload['coupon_codes'] as $code) {
                $normalized = strtoupper(preg_replace('/\s+/', '', (string) $code) ?? '');
                if ($normalized !== '') {
                    $codes[] = $normalized;
                }
            }
        } elseif (! empty($payload['coupon_code'])) {
            $normalized = strtoupper(preg_replace('/\s+/', '', (string) $payload['coupon_code']) ?? '');
            if ($normalized !== '') {
                $codes[] = $normalized;
            }
        }

        $codes = array_values(array_unique($codes));
        $quotes = collect();
        foreach ($codes as $code) {
            $quote = $this->coupons->quote($user, $code, $lines);
            if ($quote !== null) {
                $quotes->push($quote);
            }
        }

        return $quotes;
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
        $last = Order::query()
            ->pluck('order_number')
            ->reduce(function (int $max, mixed $value): int {
                $raw = trim((string) $value);
                if ($raw !== '' && ctype_digit($raw)) {
                    return max($max, (int) $raw);
                }

                return $max;
            }, 0);

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
