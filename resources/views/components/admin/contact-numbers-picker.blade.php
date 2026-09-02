@php
    use App\Support\Phone;

    $rows = collect($rows ?? [])->map(function ($row) {
        if (! is_array($row)) {
            return ['name' => '', 'phone' => '', 'phone_country' => Phone::countryCode(), 'phone_national' => ''];
        }

        $split = Phone::splitGcc((string) ($row['phone'] ?? ''));

        return [
            'name' => (string) ($row['name'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
            'phone_country' => (string) ($row['phone_country'] ?? ($split['country_code'] ?? Phone::countryCode())),
            'phone_national' => (string) ($row['phone_national'] ?? ($split['national'] ?? '')),
        ];
    })->filter(fn ($row) => $row['name'] !== '' || $row['phone'] !== '' || $row['phone_national'] !== '')->values();

    if ($rows->isEmpty()) {
        $rows = collect([[
            'name' => '',
            'phone' => '',
            'phone_country' => Phone::countryCode(),
            'phone_national' => '',
        ]]);
    }
@endphp

<div class="contact-numbers-picker" data-contact-numbers-picker>
    <div data-contact-numbers-list>
        @foreach ($rows as $index => $row)
            <div class="contact-number-row" data-contact-number-row>
                <div class="contact-number-row__name">
                    <label class="form-label small mb-1">الاسم</label>
                    <input
                        type="text"
                        name="customer_service_numbers[{{ $index }}][name]"
                        value="{{ $row['name'] }}"
                        class="form-control form-control-sm"
                        placeholder="مثال: خدمة العملاء"
                    >
                </div>
                <div class="contact-number-row__phone">
                    <label class="form-label small mb-1">رقم الجوال</label>
                    @php
                        $split = Phone::splitGcc($row['phone']);
                        $country = old("customer_service_numbers.{$index}.phone_country", $row['phone_country'] ?: ($split['country_code'] ?? Phone::countryCode()));
                        $national = old("customer_service_numbers.{$index}.phone", $row['phone_national'] ?: ($split['national'] ?? ''));
                    @endphp
                    @include('components.admin.partials.gcc-phone-field-markup', [
                        'countryName' => "customer_service_numbers[{$index}][phone_country]",
                        'nationalName' => "customer_service_numbers[{$index}][phone]",
                        'country' => $country,
                        'national' => $national,
                        'size' => 'sm',
                        'useDataFields' => false,
                    ])
                </div>
                <button type="button" class="btn btn-light btn-sm contact-number-row__remove" data-contact-number-remove aria-label="حذف">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        @endforeach
    </div>

    <button type="button" class="btn btn-outline-success btn-sm rounded-pill mt-3" data-contact-number-add>
        <i class="bi bi-plus-lg"></i>
        إضافة رقم
    </button>

    <template data-contact-number-template>
        <div class="contact-number-row" data-contact-number-row>
            <div class="contact-number-row__name">
                <label class="form-label small mb-1">الاسم</label>
                <input type="text" data-field="name" class="form-control form-control-sm" placeholder="مثال: خدمة العملاء">
            </div>
            <div class="contact-number-row__phone">
                <label class="form-label small mb-1">رقم الجوال</label>
                @include('components.admin.partials.gcc-phone-field-markup', [
                    'country' => Phone::countryCode(),
                    'national' => '',
                    'size' => 'sm',
                    'useDataFields' => true,
                    'countryDataField' => 'phone_country',
                    'nationalDataField' => 'phone',
                ])
            </div>
            <button type="button" class="btn btn-light btn-sm contact-number-row__remove" data-contact-number-remove aria-label="حذف">
                <i class="bi bi-trash"></i>
            </button>
        </div>
    </template>
</div>
