@php
    $gEntity = $globalSiteSeo['entity'] ?? null;
    $gTechnical = $globalSiteSeo['technical'] ?? null;
    $gBusiness = $globalSiteSeo['business'] ?? null;

    $metaDescription = null;
    if (isset($page) && filled($page->meta_description)) {
        $metaDescription = $page->meta_description;
    } elseif (isset($blog) && filled($blog->meta_description)) {
        $metaDescription = $blog->meta_description;
    } elseif ($gEntity !== null && filled($gEntity->meta_description)) {
        $metaDescription = $gEntity->meta_description;
    }

    $ogTitle = null;
    if (isset($page)) {
        $ogTitle = $page->meta_title ?? $page->title;
    } elseif (isset($blog)) {
        $ogTitle = $blog->meta_title ?? $blog->title;
    } elseif ($gEntity !== null && filled($gEntity->meta_title)) {
        $ogTitle = $gEntity->meta_title;
    } else {
        $ogTitle = config('app.name');
    }

    $ogDescription = $metaDescription;
    $ogImage = null;
    if (isset($blog) && filled($blog->featured_image)) {
        $ogImage = \Illuminate\Support\Str::startsWith($blog->featured_image, ['http://', 'https://'])
            ? $blog->featured_image
            : asset('storage/'.$blog->featured_image);
    } elseif ($gEntity !== null && filled($gEntity->og_image_url)) {
        $ogImage = $gEntity->og_image_url;
    } elseif ($gEntity !== null && filled($gEntity->logo)) {
        $ogImage = $gEntity->logo;
    }

    $robotsContent = 'index, follow';
    if ($gTechnical !== null && ! $gTechnical->indexable) {
        $robotsContent = 'noindex, nofollow';
    }

    $canonicalHref = ($gTechnical !== null && filled($gTechnical->canonical_url))
        ? $gTechnical->canonical_url
        : url()->current();
@endphp

@if (filled($metaDescription))
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $metaDescription), 320, '') }}">
@endif

@if ($gTechnical !== null && filled($gTechnical->google_site_verification))
    <meta name="google-site-verification" content="{{ $gTechnical->google_site_verification }}">
@endif

<meta name="robots" content="{{ $robotsContent }}">

<link rel="canonical" href="{{ $canonicalHref }}">

<meta property="og:type" content="{{ isset($blog) ? 'article' : 'website' }}">
<meta property="og:title" content="{{ strip_tags((string) $ogTitle) }}">
@if (filled($ogDescription))
    <meta property="og:description" content="{{ \Illuminate\Support\Str::limit(strip_tags((string) $ogDescription), 320, '') }}">
@endif
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
@if (filled($ogImage))
    <meta property="og:image" content="{{ $ogImage }}">
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
@endphp

@if ($gEntity !== null || $gBusiness !== null)
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
            if (filled($gBusiness->postal_code)) {
                $address['postalCode'] = $gBusiness->postal_code;
            }
            if (filled($gBusiness->country_code)) {
                $address['addressCountry'] = $gBusiness->country_code;
            }
        }

        $sameAsList = ($gEntity !== null && is_array($gEntity->same_as) && count($gEntity->same_as) > 0)
            ? array_values($gEntity->same_as)
            : null;

        $postal = $address === [] ? null : array_merge(['@type' => 'PostalAddress'], $address);

        $org = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'MedicalOrganization',
            'name' => $gEntity?->organization_name ?? $gBusiness?->name,
            'url' => config('app.url'),
            'logo' => $gEntity?->logo,
            'image' => $gEntity?->og_image_url,
            'telephone' => $gBusiness?->phone_e164 ?? $gBusiness?->phone,
            'sameAs' => $sameAsList,
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
