@php
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
    $schema = isset($page) && is_array($page->schema_json) ? $page->schema_json : null;
    $isUnifiedGraph = is_array($schema) && isset($schema['@graph']);
@endphp
@if ($isUnifiedGraph)
    <script type="application/ld+json">{!! json_encode($schema, $flags) !!}</script>
@elseif (is_array($schema) && isset($schema['@context']))
    <script type="application/ld+json">{!! json_encode($schema, $flags) !!}</script>
@endif
