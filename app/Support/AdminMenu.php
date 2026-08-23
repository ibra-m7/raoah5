<?php

namespace App\Support;

final class AdminMenu
{
    /**
     * @return list<array{title: string, icon: string, items: list<array{label: string, route: string, icon: string}>}>
     */
    public static function groups(): array
    {
        return [
            [
                'title' => '',
                'icon' => 'bi-speedometer2',
                'items' => [
                    [
                        'label' => AppStrings::DASHBOARD,
                        'route' => 'admin.dashboard',
                        'icon' => 'bi-speedometer2',
                    ],
                ],
            ],
            [
                'title' => AppStrings::NAV_CATALOG,
                'icon' => 'bi-bag',
                'items' => [
                    ['label' => AppStrings::NAV_PRODUCTS, 'route' => 'admin.products.index', 'icon' => 'bi-box-seam'],
                    ['label' => AppStrings::NAV_CATEGORIES, 'route' => 'admin.categories.index', 'icon' => 'bi-grid'],
                    ['label' => AppStrings::NAV_OFFERS, 'route' => 'admin.offers.index', 'icon' => 'bi-percent'],
                    ['label' => AppStrings::NAV_COUPONS, 'route' => 'admin.coupons.index', 'icon' => 'bi-ticket-perforated'],
                    ['label' => AppStrings::NAV_BANNERS, 'route' => 'admin.banners.index', 'icon' => 'bi-image'],
                    ['label' => AppStrings::NAV_DYNAMIC_PAGES, 'route' => 'admin.dynamic-pages.index', 'icon' => 'bi-layout-text-window-reverse'],
                    ['label' => AppStrings::NAV_HOME_SECTIONS, 'route' => 'admin.home-sections.index', 'icon' => 'bi-house'],
                ],
            ],
            [
                'title' => AppStrings::NAV_SALES,
                'icon' => 'bi-receipt',
                'items' => [
                    ['label' => AppStrings::NAV_ORDERS, 'route' => 'admin.orders.index', 'icon' => 'bi-bag-check'],
                    ['label' => AppStrings::NAV_CUSTOMERS, 'route' => 'admin.customers.index', 'icon' => 'bi-people'],
                    ['label' => AppStrings::NAV_COURIERS, 'route' => 'admin.couriers.index', 'icon' => 'bi-bicycle'],
                    ['label' => AppStrings::NAV_REVIEWS, 'route' => 'admin.reviews.index', 'icon' => 'bi-star'],
                ],
            ],
            [
                'title' => AppStrings::NAV_SETTINGS,
                'icon' => 'bi-gear',
                'items' => [
                    ['label' => AppStrings::NAV_PAGES, 'route' => 'admin.pages.index', 'icon' => 'bi-file-text'],
                    ['label' => AppStrings::NAV_ONBOARDING, 'route' => 'admin.onboarding.index', 'icon' => 'bi-collection'],
                    ['label' => AppStrings::NAV_NOTIFICATIONS, 'route' => 'admin.notifications.index', 'icon' => 'bi-bell'],
                    ['label' => AppStrings::NAV_PAYMENT_METHODS, 'route' => 'admin.payment-methods.index', 'icon' => 'bi-credit-card'],
                    ['label' => AppStrings::NAV_DELIVERY, 'route' => 'admin.delivery.index', 'icon' => 'bi-truck'],
                    ['label' => AppStrings::NAV_AI, 'route' => 'admin.ai.index', 'icon' => 'bi-stars'],
                    ['label' => AppStrings::NAV_SETTINGS, 'route' => 'admin.settings.index', 'icon' => 'bi-sliders'],
                ],
            ],
        ];
    }
}
