@php
    $rows = collect($rows ?? [])->map(function ($row) {
        if (! is_array($row)) {
            return ['name' => '', 'phone' => ''];
        }

        return [
            'name' => (string) ($row['name'] ?? ''),
            'phone' => (string) ($row['phone'] ?? ''),
        ];
    })->filter(fn ($row) => $row['name'] !== '' || $row['phone'] !== '')->values();

    if ($rows->isEmpty()) {
        $rows = collect([['name' => '', 'phone' => '']]);
    }
@endphp

<div class="contact-numbers-picker" data-contact-numbers-picker>
    <div data-contact-numbers-list>
        @foreach ($rows as $index => $row)
            <div class="row g-2 align-items-end mb-2" data-contact-number-row>
                <div class="col-md-5">
                    <label class="form-label small mb-1">الاسم</label>
                    <input
                        type="text"
                        name="customer_service_numbers[{{ $index }}][name]"
                        value="{{ $row['name'] }}"
                        class="form-control form-control-sm"
                        placeholder="مثال: خدمة العملاء"
                    >
                </div>
                <div class="col-md-5">
                    <label class="form-label small mb-1">رقم الجوال</label>
                    <input
                        type="text"
                        name="customer_service_numbers[{{ $index }}][phone]"
                        value="{{ $row['phone'] }}"
                        class="form-control form-control-sm"
                        placeholder="9677xxxxxxx"
                        dir="ltr"
                    >
                </div>
                <div class="col-md-2 d-grid">
                    <button type="button" class="btn btn-outline-danger btn-sm" data-contact-number-remove>
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>

    <button type="button" class="btn btn-outline-success btn-sm rounded-pill mt-2" data-contact-number-add>
        <i class="bi bi-plus-lg"></i>
        إضافة رقم
    </button>

    <template data-contact-number-template>
        <div class="row g-2 align-items-end mb-2" data-contact-number-row>
            <div class="col-md-5">
                <label class="form-label small mb-1">الاسم</label>
                <input type="text" data-field="name" class="form-control form-control-sm" placeholder="مثال: خدمة العملاء">
            </div>
            <div class="col-md-5">
                <label class="form-label small mb-1">رقم الجوال</label>
                <input type="text" data-field="phone" class="form-control form-control-sm" placeholder="9677xxxxxxx" dir="ltr">
            </div>
            <div class="col-md-2 d-grid">
                <button type="button" class="btn btn-outline-danger btn-sm" data-contact-number-remove>
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    </template>
</div>
