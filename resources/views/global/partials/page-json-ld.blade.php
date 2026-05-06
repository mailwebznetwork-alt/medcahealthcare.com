@php
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
@endphp
@if (isset($page) && is_array($page->schema_json) && count($page->schema_json) > 0)
    @php
        $chunks = $page->schema_json;
        if (isset($chunks['@context'])) {
            $chunks = [$chunks];
        }
    @endphp
    @foreach ($chunks as $chunk)
        @if (is_array($chunk))
            <script type="application/ld+json">{!! json_encode($chunk, $flags) !!}</script>
        @endif
    @endforeach
@endif
