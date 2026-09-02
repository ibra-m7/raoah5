@php
    use App\Support\Phone;

    $catalog = Phone::countryCatalog();
    $selected = $catalog[$country] ?? $catalog[Phone::countryCode()];
    $sizeClass = ($size ?? '') === 'sm' ? 'gcc-phone-field--sm' : '';
    $countryFieldAttr = $useDataFields ?? false
        ? 'data-field="'.($countryDataField ?? 'phone_country').'"'
        : 'name="'.($countryName ?? '').'"';
    $nationalFieldAttr = $useDataFields ?? false
        ? 'data-field="'.($nationalDataField ?? 'phone').'"'
        : 'name="'.($nationalName ?? '').'"';
@endphp

<div class="gcc-phone-field {{ $sizeClass }}" data-gcc-phone-field dir="ltr">
    <input
        type="hidden"
        {!! $countryFieldAttr !!}
        value="{{ $country }}"
        data-gcc-phone-country-input
    >
    <button
        type="button"
        class="gcc-phone-field__country"
        data-gcc-phone-country-trigger
        aria-haspopup="listbox"
        aria-expanded="false"
    >
        <span class="gcc-phone-field__flag" data-gcc-phone-flag>{{ $selected['flag'] }}</span>
        <span class="gcc-phone-field__dial" data-gcc-phone-dial>{{ $selected['dial'] }}</span>
        <i class="bi bi-chevron-down gcc-phone-field__chevron" aria-hidden="true"></i>
    </button>
    <div class="gcc-phone-field__menu" data-gcc-phone-menu hidden>
        @foreach ($catalog as $code => $item)
            <button
                type="button"
                class="gcc-phone-field__option @if((string) $country === (string) $code) is-active @endif"
                data-gcc-phone-option
                data-code="{{ $code }}"
                data-flag="{{ $item['flag'] }}"
                data-dial="{{ $item['dial'] }}"
                data-placeholder="{{ Phone::nationalPlaceholder($code) }}"
            >
                <span class="gcc-phone-field__option-flag">{{ $item['flag'] }}</span>
                <span class="gcc-phone-field__option-name">{{ $item['name'] }}</span>
                <span class="gcc-phone-field__option-dial">{{ $item['dial'] }}</span>
            </button>
        @endforeach
    </div>
    <input
        type="text"
        {!! $nationalFieldAttr !!}
        value="{{ $national }}"
        class="gcc-phone-field__national @if(!empty($hasError)) is-invalid @endif"
        placeholder="{{ Phone::nationalPlaceholder((string) $country) }}"
        inputmode="numeric"
        data-gcc-phone-national
        @if($required ?? false) required @endif
    >
</div>
