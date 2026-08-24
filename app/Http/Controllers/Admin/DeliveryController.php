<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DeliveryPerkRequest;
use App\Http\Requests\Admin\DeliveryRuleRequest;
use App\Http\Requests\Admin\DeliverySettingsRequest;
use App\Http\Requests\Admin\DeliverySlotWindowRequest;
use App\Models\DeliveryPerk;
use App\Models\DeliveryRule;
use App\Models\DeliverySlotWindow;
use App\Models\Setting;
use App\Support\AppStrings;
use App\Support\Constants;
use App\Support\DeliverySettings;
use App\Support\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    public function index(): View
    {
        $rules = DeliveryRule::query()
            ->orderBy('sort_order')
            ->orderBy('min_km')
            ->get();
        $perks = DeliveryPerk::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $slots = DeliverySlotWindow::query()->ordered()->get();

        return view('admin.delivery.index', [
            'title' => AppStrings::NAV_DELIVERY,
            'rules' => $rules,
            'perks' => $perks,
            'slots' => $slots,
            'settings' => [
                'delivery_enabled' => DeliverySettings::enabled(),
                'delivery_first_order_free' => DeliverySettings::firstOrderFree(),
                'delivery_store_lat' => DeliverySettings::storeLat(),
                'delivery_store_lng' => DeliverySettings::storeLng(),
                'delivery_store_address' => DeliverySettings::storeAddress(),
                'delivery_max_km' => DeliverySettings::maxKm(),
                'delivery_fallback_fee' => DeliverySettings::fallbackFee(),
                'free_shipping_threshold' => StoreSettings::freeShippingThreshold(),
            ],
        ]);
    }

    public function updateSettings(DeliverySettingsRequest $request): RedirectResponse
    {
        $data = $request->validated();

        Setting::setValue(Constants::SETTING_DELIVERY_ENABLED, $data['delivery_enabled'] ? '1' : '0');
        Setting::setValue(Constants::SETTING_DELIVERY_FIRST_ORDER_FREE, $data['delivery_first_order_free'] ? '1' : '0');
        Setting::setValue(Constants::SETTING_DELIVERY_STORE_LAT, $data['delivery_store_lat'] ?? '');
        Setting::setValue(Constants::SETTING_DELIVERY_STORE_LNG, $data['delivery_store_lng'] ?? '');
        Setting::setValue(Constants::SETTING_DELIVERY_STORE_ADDRESS, $data['delivery_store_address'] ?? '');
        Setting::setValue(Constants::SETTING_DELIVERY_MAX_KM, $data['delivery_max_km'] ?? '');
        Setting::setValue(Constants::SETTING_DELIVERY_FALLBACK_FEE, $data['delivery_fallback_fee'] ?? StoreSettings::shippingFee());
        if (array_key_exists('free_shipping_threshold', $data) && $data['free_shipping_threshold'] !== null) {
            Setting::setValue(Constants::SETTING_FREE_SHIPPING_THRESHOLD, $data['free_shipping_threshold']);
        }

        return back()->with('success', AppStrings::DELIVERY_SETTINGS_SAVED);
    }

    public function create(): RedirectResponse
    {
        return redirect()->route('admin.delivery.index');
    }

    public function store(DeliveryRuleRequest $request): RedirectResponse
    {
        DeliveryRule::query()->create($request->validated());

        return redirect()
            ->route('admin.delivery.index')
            ->with('success', AppStrings::DELIVERY_RULE_CREATED);
    }

    public function edit(DeliveryRule $delivery_rule): RedirectResponse
    {
        return redirect()->route('admin.delivery.index');
    }

    public function update(DeliveryRuleRequest $request, DeliveryRule $delivery_rule): RedirectResponse
    {
        $delivery_rule->update($request->validated());

        return redirect()
            ->route('admin.delivery.index')
            ->with('success', AppStrings::DELIVERY_RULE_UPDATED);
    }

    public function destroy(DeliveryRule $delivery_rule): RedirectResponse
    {
        $delivery_rule->delete();

        return redirect()
            ->route('admin.delivery.index')
            ->with('success', AppStrings::DELIVERY_RULE_DELETED);
    }

    public function createPerk(): RedirectResponse
    {
        return redirect()->route('admin.delivery.index');
    }

    public function storePerk(DeliveryPerkRequest $request): RedirectResponse
    {
        DeliveryPerk::query()->create($request->validated());

        return redirect()
            ->route('admin.delivery.index')
            ->with('success', AppStrings::DELIVERY_PERK_CREATED);
    }

    public function editPerk(DeliveryPerk $delivery_perk): RedirectResponse
    {
        return redirect()->route('admin.delivery.index');
    }

    public function updatePerk(DeliveryPerkRequest $request, DeliveryPerk $delivery_perk): RedirectResponse
    {
        $delivery_perk->update($request->validated());

        return redirect()
            ->route('admin.delivery.index')
            ->with('success', AppStrings::DELIVERY_PERK_UPDATED);
    }

    public function destroyPerk(DeliveryPerk $delivery_perk): RedirectResponse
    {
        $delivery_perk->delete();

        return redirect()
            ->route('admin.delivery.index')
            ->with('success', AppStrings::DELIVERY_PERK_DELETED);
    }

    public function storeSlot(DeliverySlotWindowRequest $request): RedirectResponse
    {
        DeliverySlotWindow::query()->create($request->validated());

        return redirect()
            ->route('admin.delivery.index')
            ->with('success', AppStrings::DELIVERY_SLOT_CREATED);
    }

    public function updateSlot(DeliverySlotWindowRequest $request, DeliverySlotWindow $delivery_slot_window): RedirectResponse
    {
        $delivery_slot_window->update($request->validated());

        return redirect()
            ->route('admin.delivery.index')
            ->with('success', AppStrings::DELIVERY_SLOT_UPDATED);
    }

    public function destroySlot(DeliverySlotWindow $delivery_slot_window): RedirectResponse
    {
        $delivery_slot_window->delete();

        return redirect()
            ->route('admin.delivery.index')
            ->with('success', AppStrings::DELIVERY_SLOT_DELETED);
    }
}
