@php
    $gEntity = $globalSiteSeo['entity'] ?? null;
    $gTechnical = $globalSiteSeo['technical'] ?? null;
    $gBusiness = $globalSiteSeo['business'] ?? null;

    if (isset($page) && isset($page->content)) {
        \App\Services\ContentParser::preregister($page->content);
    }
    if (isset($service)) {
        app(\App\Services\ServiceContextCollector::class)->register($service);
    }

    $metaDescription = null;
    if (isset($documentMeta) && filled($documentMeta['meta_description'] ?? null)) {
        $metaDescription = $documentMeta['meta_description'];
    } elseif (isset($page) && filled($page->meta_description)) {
        $metaDescription = $page->meta_description;
    } elseif (isset($blog) && filled($blog->meta_description)) {
        $metaDescription = $blog->meta_description;
    } elseif ($gEntity !== null && filled($gEntity->meta_description)) {
        $metaDescription = $gEntity->meta_description;
    }

    $ogTitle = null;
    if (isset($documentMeta) && filled($documentMeta['meta_title'] ?? null)) {
        $ogTitle = $documentMeta['meta_title'];
    } elseif (isset($page)) {
        $ogTitle = $page->meta_title ?? $page->title;
    } elseif (isset($blog)) {
        $ogTitle = $blog->meta_title ?? $blog->title;
    } elseif ($gEntity !== null && filled($gEntity->meta_title)) {
        $ogTitle = $gEntity->meta_title;
    } else {
        $ogTitle = config('medca.brand_name');
    }

    $ogDescription = $metaDescription;
    $ogImage = null;
    if (isset($blog) && filled($blog->featured_image)) {
        $ogImage = \Illuminate\Support\Str::startsWith($blog->featured_image, ['http://', 'https://'])
            ? $blog->featured_image
            : asset('storage/'.$blog->featured_image);
    } elseif (isset($page) && filled($page->og_image)) {
        $ogImage = \Illuminate\Support\Str::startsWith($page->og_image, ['http://', 'https://'])
            ? $page->og_image
            : asset('storage/'.$page->og_image);
    } elseif ($gEntity !== null && filled($gEntity->og_image_url)) {
        $ogImage = $gEntity->og_image_url;
    } elseif ($gEntity !== null && filled($gEntity->logo)) {
        $ogImage = $gEntity->logo;
    }

    $robotsContent = 'index, follow';
    if (isset($page) && filled($page->robots_meta)) {
        $robotsContent = $page->robots_meta;
    } elseif ($gTechnical !== null && ! $gTechnical->indexable) {
        $robotsContent = 'noindex, nofollow';
    }

    $canonicalHref = url()->current();
    if (isset($page) && filled($page->canonical_url)) {
        $canonicalHref = $page->canonical_url;
    } elseif ($gTechnical !== null && filled($gTechnical->canonical_url)) {
        $canonicalHref = $gTechnical->canonical_url;
    }
@endphp

@if (filled($metaDescription))
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $metaDescription), 320, '') }}">
@endif

@if ($gTechnical !== null && filled($gTechnical->google_site_verification))
    <meta name="google-site-verification" content="{{ $gTechnical->google_site_verification }}">
@endif

<meta name="robots" content="{{ $robotsContent }}">

<link rel="canonical" href="{{ $canonicalHref }}">

@if (isset($documentMeta) && is_array($documentMeta['hreflang'] ?? null) && count($documentMeta['hreflang']) > 0)
    @foreach ($documentMeta['hreflang'] as $locale => $hrefLangUrl)
        @if (filled($hrefLangUrl))
            <link rel="alternate" hreflang="{{ $locale }}" href="{{ $hrefLangUrl }}">
        @endif
    @endforeach
@elseif (isset($page) && is_array($page->hreflang_json) && count($page->hreflang_json) > 0)
    @foreach ($page->hreflang_json as $locale => $hrefLangUrl)
        @if (filled($hrefLangUrl))
            <link rel="alternate" hreflang="{{ $locale }}" href="{{ $hrefLangUrl }}">
        @endif
    @endforeach
@endif

<meta property="og:type" content="{{ isset($blog) ? 'article' : 'website' }}">
<meta property="og:title" content="{{ strip_tags((string) $ogTitle) }}">
@if (filled($ogDescription))
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $ogDescription), 320, '') }}">
@endif
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:site_name" content="{{ config('medca.brand_name') }}">
@if (filled($ogImage))
    <meta property="og:image" content="{{ $ogImage }}">
@endif
@if (isset($page) && filled($page->og_image_alt))
    <meta property="og:image:alt" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $page->og_image_alt), 420, '') }}">
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ strip_tags((string) $ogTitle) }}">
@if (filled($ogDescription))
    <meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $ogDescription), 320, '') }}">
@endif
@if (filled($ogImage))
    <meta name="twitter:image" content="{{ $ogImage }}">
@endif

@php
    $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT;
    $pageUsesUnifiedGraph = isset($page)
        && is_array($page->schema_json ?? null)
        && isset($page->schema_json['@graph']);
    $pageCategoryValue = isset($page->page_category) ? $page->page_category->value ?? $page->page_category : null;
    $suppressDuplicateSchema = $pageUsesUnifiedGraph
        || in_array($pageCategoryValue, ['service', 'location'], true);
@endphp

@if (! $suppressDuplicateSchema && ($gEntity !== null || $gBusiness !== null))
    @php
        $address = [];
        if ($gBusiness !== null) {
            if (filled($gBusiness->street_address)) {
                $address['streetAddress'] = $gBusiness->street_address;
            }
            if (filled($gBusiness->city)) {
                $address['addressLocality'] = $gBusiness->city;
            }
            if (filled($gBusiness->region)) {
                $address['addressRegion'] = $gBusiness->region;
            }
            if (filled($gBusiness->country_code)) {
                $address['addressCountry'] = $gBusiness->country_code;
            }
        }

        $sameAsList = ($gEntity !== null && is_array($gEntity->same_as) && count($gEntity->same_as) > 0)
            ? array_values($gEntity->same_as)
            : null;

        $postal = $address === [] ? null : array_merge(['@type' => 'PostalAddress'], $address);

        $identifier = null;
        if ($gEntity !== null && filled($gEntity->google_place_id)) {
            $identifier = [
                '@type' => 'PropertyValue',
                'propertyID' => 'Google Place ID',
                'value' => $gEntity->google_place_id,
            ];
        }

        $org = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'ProfessionalService',
            'name' => $gEntity?->organization_name ?? $gBusiness?->name,
            'url' => config('app.url'),
            'logo' => $gEntity?->logo,
            'image' => $gEntity?->og_image_url,
            'telephone' => $gBusiness?->phone_e164 ?? $gBusiness?->phone,
            'sameAs' => $sameAsList,
            'hasMap' => $gEntity?->has_map_url,
            'identifier' => $identifier,
            'address' => $postal,
        ], function (mixed $v): bool {
            if ($v === null || $v === '') {
                return false;
            }

            if (is_array($v) && $v === []) {
                return false;
            }

            return true;
        });
    @endphp
    @if ($org !== [])
        <script type="application/ld+json">{!! json_encode($org, $jsonFlags) !!}</script>
    @endif
@endif

@if (! $suppressDuplicateSchema && isset($page) && $page->relationLoaded('faqs') && $page->faqs->isNotEmpty())
    @php
        $pageFaqMain = [];
        foreach ($page->faqs as $pageFaq) {
            if (! filled($pageFaq->question) || ! filled($pageFaq->answer)) {
                continue;
            }
            $pageFaqMain[] = [
                '@type' => 'Question',
                'name' => $pageFaq->question,
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $pageFaq->answer,
                ],
            ];
        }
        $pageFaqLd = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $pageFaqMain,
        ];
    @endphp
    @if (count($pageFaqMain) > 0)
        <script type="application/ld+json">{!! json_encode($pageFaqLd, $jsonFlags) !!}</script>
    @endif
@endif

@if (! $suppressDuplicateSchema && $gEntity !== null && is_array($gEntity->entity_faqs) && count($gEntity->entity_faqs) > 0)
    @php
        $faqMain = [];
        foreach ($gEntity->entity_faqs as $row) {
            if (! is_array($row) || empty($row['question']) || empty($row['answer'])) {
                continue;
            }
            $faqMain[] = [
                '@type' => 'Question',
                'name' => $row['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $row['answer'],
                ],
            ];
        }
        $faqLd = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $faqMain,
        ];
    @endphp
    @if (count($faqMain) > 0)
        <script type="application/ld+json">{!! json_encode($faqLd, $jsonFlags) !!}</script>
    @endif
@endif

@if ($gEntity !== null && ! empty($gEntity->custom_json_ld))
    @php
        $ldChunks = $gEntity->custom_json_ld;
        if (is_array($ldChunks) && isset($ldChunks['@context'])) {
            $ldChunks = [$ldChunks];
        }
    @endphp
    @if (is_array($ldChunks))
        @foreach ($ldChunks as $chunk)
            @if (is_array($chunk))
                <script type="application/ld+json">{!! json_encode($chunk, $jsonFlags) !!}</script>
            @endif
        @endforeach
    @endif
@endif

@php
    /** @var \App\Services\ServiceContextCollector|null $serviceContextCollector */
    $serviceContextCollector = $serviceContextCollector ?? app(\App\Services\ServiceContextCollector::class);
    $collectedServices = $serviceContextCollector->collected();
@endphp

@if (! $suppressDuplicateSchema && $collectedServices->isNotEmpty())
    @foreach ($collectedServices as $serviceItem)
        @php
            $serviceLd = array_merge(['@context' => 'https://schema.org'], $serviceItem->toServiceSchema());
        @endphp
        <script type="application/ld+json">{!! json_encode($serviceLd, $jsonFlags) !!}</script>

        @if ($serviceItem->schema && is_array($serviceItem->schema->schema_json) && $serviceItem->schema->schema_json !== [])
            @php
                $customServiceLd = $serviceItem->schema->schema_json;
                if (! isset($customServiceLd['@context'])) {
                    $customServiceLd = array_merge(['@context' => 'https://schema.org'], $customServiceLd);
                }
            @endphp
            <script type="application/ld+json">{!! json_encode($customServiceLd, $jsonFlags) !!}</script>
        @endif
    @endforeach

    @php
        $aggregatedFaqs = [];
        foreach ($collectedServices as $serviceItem) {
            foreach ($serviceItem->toFaqEntities() as $entity) {
                $aggregatedFaqs[] = $entity;
            }
        }
    @endphp
    @if ($aggregatedFaqs !== [])
        @php
            $serviceFaqLd = [
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $aggregatedFaqs,
            ];
        @endphp
        <script type="application/ld+json">{!! json_encode($serviceFaqLd, $jsonFlags) !!}</script>
    @endif
@endif
