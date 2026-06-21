@php
    use App\Models\PinCode;
    use App\Models\Service;
    use App\Models\ServiceLocationPage;
    use App\Services\Operations\ServiceSchemaGenerator;

    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
    $schema = isset($page) && is_array($page->schema_json) ? $page->schema_json : null;
    $preferLiveSchema = ($documentMeta['prefer_live_schema'] ?? false) === true;

    if ($preferLiveSchema && isset($service) && $service instanceof Service) {
        $generator = app(ServiceSchemaGenerator::class);

        if (isset($serviceLocation) && $serviceLocation instanceof ServiceLocationPage) {
            $serviceLocation->loadMissing(['country', 'service']);
            $pin = $serviceLocation->pincode;

            if ($pin instanceof PinCode) {
                $schema = $generator->buildLocationGraph($service, $pin, $serviceLocation);
            }
        } elseif (! isset($serviceLocation)) {
            $schema = $generator->buildGraph($service);
        }
    }

    $isUnifiedGraph = is_array($schema) && isset($schema['@graph']);
@endphp
@if ($isUnifiedGraph)
    <script type="application/ld+json">{!! json_encode($schema, $flags) !!}</script>
@elseif (is_array($schema) && isset($schema['@context']))
    <script type="application/ld+json">{!! json_encode($schema, $flags) !!}</script>
@endif
