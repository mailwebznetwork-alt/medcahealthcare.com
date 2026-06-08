@php
    use App\Services\Public\PublicPagePresenter;
    use App\Support\BlockContent;

    $settings = is_array($blockSettings ?? null) ? $blockSettings : [];
    $slug = is_string($blockSlug ?? null) && $blockSlug !== ''
        ? $blockSlug
        : 'near-you-home';
    $contentSlug = $slug === 'near-you-locations' ? 'near-you-home' : $slug;

    $payload = is_array($nearYouPayload ?? null)
        ? $nearYouPayload
        : app(PublicPagePresenter::class)->nearYouPayload();
@endphp
@include('public.partials.near-you-services', array_merge($payload, [
    'blockSettings' => $settings,
    'contentSlug' => BlockContent::hasSchema($contentSlug) ? $contentSlug : null,
]))
