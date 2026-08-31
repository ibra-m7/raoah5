<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AdminEvent;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Support\AppStrings;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'title' => AppStrings::DASHBOARD,
            'stats' => [
                [
                    'label' => AppStrings::STAT_PRODUCTS,
                    'value' => Product::query()->count(),
                    'icon' => 'bi-box-seam',
                    'tone' => 'primary',
                ],
                [
                    'label' => AppStrings::STAT_ORDERS,
                    'value' => Order::query()->count(),
                    'icon' => 'bi-bag-check',
                    'tone' => 'info',
                ],
                [
                    'label' => AppStrings::STAT_CUSTOMERS,
                    'value' => User::query()->where('role', UserRole::Customer)->count(),
                    'icon' => 'bi-people',
                    'tone' => 'warning',
                ],
                [
                    'label' => AppStrings::STAT_REVENUE,
                    'value' => number_format((float) Order::query()
                        ->where('status', OrderStatus::Delivered)
                        ->sum('total'), 2).' '.AppStrings::CURRENCY,
                    'icon' => 'bi-wallet2',
                    'tone' => 'success',
                ],
            ],
            'shortcuts' => [
                [
                    'label' => AppStrings::NAV_HOME_SECTIONS,
                    'hint' => 'أدر شرائط المنتجات وسلات التوفير في الصفحة الرئيسية',
                    'route' => 'admin.home-sections.index',
                    'icon' => 'bi-house',
                ],
                [
                    'label' => AppStrings::NAV_PRODUCTS,
                    'hint' => 'أضف منتجاً جديداً للكتالوج',
                    'route' => 'admin.products.create',
                    'icon' => 'bi-box-seam',
                ],
                [
                    'label' => AppStrings::NAV_BANNERS,
                    'hint' => 'أضف إعلاناً لشريط الرئيسية',
                    'route' => 'admin.banners.create',
                    'icon' => 'bi-image',
                ],
                [
                    'label' => AppStrings::NAV_PRODUCT_DISCOUNTS,
                    'hint' => 'ضع خصماً على منتج ليظهر في شريط العروض',
                    'route' => 'admin.offers.create',
                    'icon' => 'bi-percent',
                ],
            ],
            'pendingOrders' => Order::query()
                ->with(['user', 'courier'])
                ->where('status', OrderStatus::Pending)
                ->latest()
                ->limit(8)
                ->get(),
            'liveEvents' => AdminEvent::query()
                ->latest('id')
                ->limit(8)
                ->get(),
        ]);
    }
}
