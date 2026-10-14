@php
    $store = app(\App\Services\Marketing\Attribution\AttributionSessionStore::class);
    $first = $store->firstTouch();
    $last = $store->lastTouch(request());
    $utm = array_merge($first, $last);
    $fields = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'gclid', 'fbclid'];
@endphp
@foreach ($fields as $field)
    <input
        type="hidden"
        name="{{ $field }}"
        value="{{ old($field, $utm[$field] ?? '') }}"
        data-medca-utm-field="{{ $field }}"
    />
@endforeach
