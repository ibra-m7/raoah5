<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Setting;
use App\Services\Admin\ProductService;
use App\Support\AppStrings;
use App\Support\Constants;
use App\Support\Media;
use App\Support\Phone;
use App\Support\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingController extends Controller
{
    /** @var list<string> */
    private const TABS = ['app', 'store', 'marketing', 'privacy'];

    public function __construct(private readonly ProductService $products) {}

    public function index(Request $request): View
    {
        $tab = $this->tab($request->query('tab'));
        $selectedIds = StoreSettings::marketingSoldProductIds();

        return view('admin.settings.index', [
            'title' => AppStrings::NAV_SETTINGS,
            'tab' => $tab,
            'settings' => [
                'store_name' => Setting::getValue(Constants::SETTING_STORE_NAME, AppStrings::APP_NAME),
                'currency' => Setting::getValue(Constants::SETTING_CURRENCY, AppStrings::CURRENCY),
                'shipping_fee' => Setting::getValue(Constants::SETTING_SHIPPING_FEE, Constants::SHIPPING_FEE),
                'free_shipping_threshold' => Setting::getValue(
                    Constants::SETTING_FREE_SHIPPING_THRESHOLD,
                    Constants::FREE_SHIPPING_THRESHOLD
                ),
                'bank_iban' => Setting::getValue(Constants::SETTING_BANK_IBAN, ''),
                'bank_name' => Setting::getValue(Constants::SETTING_BANK_NAME, 'البنك الأهلي السعودي'),
                'marketing_sold_count' => Setting::getValue(Constants::SETTING_MARKETING_SOLD_COUNT, 0),
                'marketing_sold_scope' => StoreSettings::marketingSoldScope(),
                'fallback_product_image' => Setting::getValue(Constants::SETTING_FALLBACK_PRODUCT_IMAGE, ''),
                'message_us_phone' => StoreSettings::messageUsPhone(),
                'customer_service_numbers' => StoreSettings::customerServiceNumbers(),
                'otp_bypass_phones' => StoreSettings::otpBypassPhones(),
            ],
            'products' => $this->products->pickerItems(
                old('marketing_sold_product_ids', $selectedIds),
            ),
            'selectedProductIds' => $selectedIds,
            'productCount' => Product::withTrashed()->count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tab = $this->tab($request->input('active_tab'));

        try {
            $data = $request->validate([
                'store_name' => ['required', 'string', 'max:255'],
                'currency' => ['required', 'string', 'max:20'],
                'shipping_fee' => ['required', 'numeric', 'min:0'],
                'free_shipping_threshold' => ['required', 'numeric', 'min:0'],
                'bank_iban' => ['nullable', 'string', 'max:40'],
                'bank_name' => ['nullable', 'string', 'max:80'],
                'marketing_sold_count' => ['nullable', 'integer', 'min:0', 'max:9999999'],
                'marketing_sold_scope' => ['required', 'in:all,selected'],
                'marketing_sold_product_ids' => ['nullable', 'array'],
                'marketing_sold_product_ids.*' => ['integer', 'exists:products,id'],
                'fallback_product_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
                'fallback_product_image_url' => ['nullable', 'url', 'max:2048'],
                'message_us_phone_country' => ['nullable', 'string', Rule::in(Phone::allowedCountryCodes())],
                'message_us_phone' => ['nullable', 'string', 'max:16'],
                'customer_service_numbers' => ['nullable', 'array'],
                'customer_service_numbers.*.name' => ['required_with:customer_service_numbers.*.phone', 'string', 'max:80'],
                'customer_service_numbers.*.phone_country' => ['nullable', 'string', Rule::in(Phone::allowedCountryCodes())],
                'customer_service_numbers.*.phone' => ['required_with:customer_service_numbers.*.name', 'string', 'max:16'],
                'otp_bypass_phones' => ['nullable', 'array'],
                'otp_bypass_phones.*.country_code' => ['nullable', 'string', Rule::in(Phone::allowedCountryCodes())],
                'otp_bypass_phones.*.national' => ['nullable', 'string', 'max:16'],
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo(route('admin.settings.index', ['tab' => $tab]));
        }

        Setting::setValue(Constants::SETTING_STORE_NAME, $data['store_name']);
        Setting::setValue(Constants::SETTING_CURRENCY, $data['currency']);
        Setting::setValue(Constants::SETTING_SHIPPING_FEE, $data['shipping_fee']);
        Setting::setValue(Constants::SETTING_FREE_SHIPPING_THRESHOLD, $data['free_shipping_threshold']);
        Setting::setValue(Constants::SETTING_BANK_IBAN, $data['bank_iban'] ?? '');
        Setting::setValue(Constants::SETTING_BANK_NAME, $data['bank_name'] ?? '');
        Setting::setValue(Constants::SETTING_MARKETING_SOLD_COUNT, (string) ($data['marketing_sold_count'] ?? 0));
        Setting::setValue(Constants::SETTING_MARKETING_SOLD_SCOPE, $data['marketing_sold_scope']);
        Setting::setValue(
            Constants::SETTING_MARKETING_SOLD_PRODUCT_IDS,
            json_encode(array_values(array_unique(array_map('intval', $data['marketing_sold_product_ids'] ?? []))))
        );

        Setting::setValue(
            Constants::SETTING_MESSAGE_US_PHONE,
            $this->normalizeGccPhoneFromParts(
                (string) ($data['message_us_phone_country'] ?? Phone::countryCode()),
                (string) ($data['message_us_phone'] ?? ''),
                'message_us_phone',
                $tab,
            ),
        );

        $contactRows = [];
        foreach ($data['customer_service_numbers'] ?? [] as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $name = trim((string) ($row['name'] ?? ''));
            $national = trim((string) ($row['phone'] ?? ''));
            if ($name === '' && $national === '') {
                continue;
            }
            $phone = $this->normalizeGccPhoneFromParts(
                (string) ($row['phone_country'] ?? Phone::countryCode()),
                $national,
                "customer_service_numbers.{$index}.phone",
                $tab,
            );
            if ($name === '' || $phone === '') {
                continue;
            }
            $contactRows[] = [
                'name' => $name,
                'phone' => $phone,
            ];
        }
        Setting::setValue(
            Constants::SETTING_CUSTOMER_SERVICE_NUMBERS,
            json_encode(array_values($contactRows)),
        );

        $bypassPhones = [];
        foreach ($data['otp_bypass_phones'] ?? [] as $index => $row) {
            if (! is_array($row)) {
                continue;
            }
            $national = trim((string) ($row['national'] ?? ''));
            if ($national === '') {
                continue;
            }
            $bypassPhones[] = $this->normalizeGccPhoneFromParts(
                (string) ($row['country_code'] ?? Phone::countryCode()),
                $national,
                "otp_bypass_phones.{$index}.national",
                $tab,
            );
        }
        Setting::setValue(
            Constants::SETTING_OTP_BYPASS_PHONES,
            json_encode(array_values(array_unique($bypassPhones))),
        );

        $currentFallback = (string) Setting::getValue(Constants::SETTING_FALLBACK_PRODUCT_IMAGE, '');
        $uploaded = $request->file('fallback_product_image');
        $fallbackUrl = trim((string) ($data['fallback_product_image_url'] ?? ''));
        if ($uploaded && $uploaded->isValid()) {
            $oldPath = ! str_starts_with($currentFallback, 'http') && ! str_starts_with($currentFallback, 'data:')
                ? $currentFallback
                : null;
            $path = Media::store($uploaded, 'settings', $oldPath);
            if (! $path) {
                throw ValidationException::withMessages([
                    'fallback_product_image' => 'تعذّر حفظ الصورة المرفوعة.',
                ])->redirectTo(route('admin.settings.index', ['tab' => $tab]));
            }

            Setting::setValue(Constants::SETTING_FALLBACK_PRODUCT_IMAGE, $path);
        } elseif ($fallbackUrl !== '') {
            if ($currentFallback && $fallbackUrl !== $currentFallback) {
                Media::delete($currentFallback);
            }
            Setting::setValue(Constants::SETTING_FALLBACK_PRODUCT_IMAGE, $fallbackUrl);
        }

        return redirect()
            ->route('admin.settings.index', ['tab' => $tab])
            ->with('success', 'تم حفظ الإعدادات بنجاح.');
    }

    public function destroyAllProducts(Request $request, ProductService $products): RedirectResponse
    {
        $phrase = AppStrings::WIPE_PRODUCTS_CONFIRMATION_PHRASE;

        try {
            $request->validate([
                'confirmation' => ['required', 'string', Rule::in([$phrase])],
            ], [
                'confirmation.in' => 'اكتب «'.$phrase.'» للتأكيد.',
                'confirmation.required' => 'اكتب «'.$phrase.'» للتأكيد.',
            ]);
        } catch (ValidationException $e) {
            throw $e->redirectTo(route('admin.settings.index', ['tab' => 'store']));
        }

        if (! hash_equals($phrase, trim((string) $request->input('confirmation')))) {
            return redirect()
                ->route('admin.settings.index', ['tab' => 'store'])
                ->withErrors(['confirmation' => 'اكتب «'.$phrase.'» للتأكيد.']);
        }

        set_time_limit(120);
        $count = $products->deleteAll();

        return redirect()
            ->route('admin.settings.index', ['tab' => 'store'])
            ->with('success', $count > 0 ? AppStrings::PRODUCTS_WIPED : 'لا توجد منتجات للحذف.');
    }

    private function tab(mixed $value): string
    {
        $tab = is_string($value) ? $value : 'app';

        return in_array($tab, self::TABS, true) ? $tab : 'app';
    }

    private function normalizeGccPhoneFromParts(string $countryCode, string $national, string $field, string $tab): string
    {
        if (trim($national) === '') {
            return '';
        }

        $normalized = Phone::combineGcc($countryCode, $national);
        if ($normalized === null) {
            throw ValidationException::withMessages([
                $field => 'الرقم غير صالح للدولة المختارة.',
            ])->redirectTo(route('admin.settings.index', ['tab' => $tab]));
        }

        return $normalized;
    }
}
