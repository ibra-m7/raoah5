@props([
    'countryName',
    'nationalName',
    'e164' => '',
    'size' => '',
    'required' => false,
])

@php
    use App\Support\Phone;
    $split = Phone::splitGcc($e164);
    $country = old($countryName, $split['country_code'] ?? Phone::countryCode());
    $national = old($nationalName, $split['national'] ?? '');
    $hasError = $errors->has($nationalName);
@endphp

@include('components.admin.partials.gcc-phone-field-markup', [
    'countryName' => $countryName,
    'nationalName' => $nationalName,
    'country' => $country,
    'national' => $national,
    'size' => $size,
    'required' => $required,
    'hasError' => $hasError,
    'useDataFields' => false,
])

@error($nationalName)
    <div class="invalid-feedback d-block">{{ $message }}</div>
@enderror
