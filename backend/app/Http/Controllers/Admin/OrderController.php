<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Courier;
use App\Models\Order;
use App\Models\Product;
use App\Services\Orders\OrderService;
use App\Support\AppStrings;
use App\Support\Constants;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orders) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'status']);

        $orders = Order::query()
            ->with(['user', 'items', 'address', 'courier'])
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('order_number', 'like', '%'.$search.'%')
                        ->orWhere('shipping_name', 'like', '%'.$search.'%')
                        ->orWhere('shipping_phone', 'like', '%'.$search.'%')
                        ->orWhereHas('courier', fn ($courier) => $courier->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(Constants::DEFAULT_PAGE_SIZE)
            ->withQueryString();

        $availableCouriers = Courier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.orders.index', [
            'title' => AppStrings::NAV_ORDERS,
            'orders' => $orders,
            'filters' => $filters,
            'statuses' => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'availableCouriers' => $availableCouriers,
        ]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'payment_status' => ['nullable', Rule::enum(PaymentStatus::class)],
        ]);

        $this->orders->updateStatus(
            $order,
            OrderStatus::from($data['status']),
            isset($data['payment_status']) ? PaymentStatus::from($data['payment_status']) : null,
        );

        return back()->with('success', 'تم تحديث الطلب '.$order->order_number.' بنجاح.');
    }

    public function updateCourier(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'courier_id' => ['required', 'integer', Rule::exists('couriers', 'id')],
        ]);

        $courier = Courier::query()->findOrFail($data['courier_id']);
        $this->orders->assignCourier($order, $courier);

        return back()->with('success', AppStrings::COURIER_CHANGED);
    }

    public function edit(Order $order): View
    {
        $order->load(['user', 'items.product', 'address', 'courier']);

        $catalog = Product::query()
            ->with(['primaryImage', 'images', 'category.parent'])
            ->where(function ($query) use ($order) {
                $query->active()->orWhereIn('id', $order->items->pluck('product_id')->filter());
            })
            ->orderBy('name')
            ->get();

        $categories = Category::query()
            ->active()
            ->roots()
            ->with(['children' => fn ($query) => $query->active()->orderBy('sort_order')->orderBy('name')])
            ->orderBy('name')
            ->get();

        return view('admin.orders.edit', [
            'title' => AppStrings::EDIT_ORDER.' '.$order->order_number,
            'order' => $order,
            'products' => $catalog,
            'categories' => $categories,
            'canEdit' => $order->status !== OrderStatus::Cancelled,
        ]);
    }

    public function updateItems(Request $request, Order $order): RedirectResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'notes' => ['nullable', 'string', 'max:500'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0', 'max:9999'],
            'recalc_shipping' => ['nullable', 'boolean'],
        ], [
            'items.required' => 'أضف منتجاً واحداً على الأقل.',
            'items.min' => 'أضف منتجاً واحداً على الأقل.',
        ]);

        $this->orders->updateItems(
            $order,
            $data['items'],
            $data['notes'] ?? null,
            array_key_exists('shipping_fee', $data) && $data['shipping_fee'] !== null ? (float) $data['shipping_fee'] : null,
            $request->boolean('recalc_shipping'),
        );

        return redirect()
            ->route('admin.orders.edit', $order)
            ->with('success', AppStrings::ORDER_ITEMS_UPDATED);
    }
}
