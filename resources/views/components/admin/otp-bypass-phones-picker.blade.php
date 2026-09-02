@php
    use App\Support\Phone;

    $phones = collect($phones ?? [])->map(function ($phone) {
        if (is_array($phone)) {
            $split = Phone::splitGcc((string) ($phone['phone'] ?? ''));
            return [
                'country_code' => (string) ($phone['country_code'] ?? ($split['country_code'] ?? Phone::countryCode())),
                'national' => (string) ($phone['national'] ?? ($split['national'] ?? '')),
                'e164' => (string) ($phone['phone'] ?? ''),
            ];
        }

        $split = Phone::splitGcc((string) $phone);

        return [
            'country_code' => $split['country_code'] ?? Phone::countryCode(),
            'national' => $split['national'] ?? '',
            'e164' => (string) $phone,
        ];
    })->filter(fn ($row) => trim($row['national']) !== '' || trim($row['e164']) !== '')->values();

    $isEmpty = $phones->isEmpty();
    $defaultCountry = '967';
    if ($isEmpty) {
        $phones = collect([[
            'country_code' => $defaultCountry,
            'national' => '',
            'e164' => '',
        ]]);
    }
@endphp

<div class="otp-bypass-phones-picker" data-otp-bypass-phones-picker>
    <div class="privacy-info-card">
        <div class="privacy-info-card__icon">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <div>
            <h3 class="privacy-info-card__title">أرقام الدخول بدون تحقق</h3>
            <p class="privacy-info-card__text mb-2">
                أي رقم مضاف هنا يدخل التطبيق مباشرة دون إرسال رمز واتساب. اختر الدولة ثم أدخل الرقم بدون رمزها.
            </p>
            <p class="privacy-info-card__example mb-0">
                <i class="bi bi-lightbulb"></i>
                مثال: اختر <strong>اليمن +967</strong> ثم أدخل <strong dir="ltr">777234341</strong>
            </p>
        </div>
    </div>

    <div class="privacy-phone-list-header">
        <span class="form-label mb-0">الأرقام المسموحة</span>
        @if ($isEmpty)
            <span class="privacy-phone-list-empty-hint">لا توجد أرقام بعد</span>
        @endif
    </div>

    <div class="privacy-phone-list" data-otp-bypass-phones-list>
        @foreach ($phones as $index => $phone)
            <div class="privacy-phone-row" data-otp-bypass-phone-row>
                <div class="privacy-phone-row__field">
                    @php
                        $split = Phone::splitGcc($phone['e164']);
                        $country = old("otp_bypass_phones.{$index}.country_code", $phone['country_code'] ?: ($split['country_code'] ?? Phone::countryCode()));
                        $national = old("otp_bypass_phones.{$index}.national", $phone['national'] ?: ($split['national'] ?? ''));
                    @endphp
                    @include('components.admin.partials.gcc-phone-field-markup', [
                        'countryName' => "otp_bypass_phones[{$index}][country_code]",
                        'nationalName' => "otp_bypass_phones[{$index}][national]",
                        'country' => $country,
                        'national' => $national,
                        'size' => 'sm',
                        'useDataFields' => false,
                    ])
                </div>
                <button type="button" class="btn btn-light btn-sm privacy-phone-row__remove" data-otp-bypass-phone-remove aria-label="حذف الرقم">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        @endforeach
    </div>

    <div class="privacy-phone-actions">
        <button type="button" class="btn btn-outline-success btn-sm rounded-pill" data-otp-bypass-phone-add>
            <i class="bi bi-plus-lg"></i>
            إضافة رقم
        </button>
    </div>

    <template data-otp-bypass-phone-template>
        <div class="privacy-phone-row" data-otp-bypass-phone-row>
            <div class="privacy-phone-row__field">
                @include('components.admin.partials.gcc-phone-field-markup', [
                    'country' => $defaultCountry,
                    'national' => '',
                    'size' => 'sm',
                    'useDataFields' => true,
                    'countryDataField' => 'country_code',
                    'nationalDataField' => 'national',
                ])
            </div>
            <button type="button" class="btn btn-light btn-sm privacy-phone-row__remove" data-otp-bypass-phone-remove aria-label="حذف الرقم">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </template>
</div>
