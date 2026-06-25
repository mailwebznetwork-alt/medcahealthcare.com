<?php

namespace App\Services\Seo;

use App\Models\PinCode;
use App\Models\PinCodeHospital;
use App\Models\PinCodeLandmark;
use App\Models\PinCodeNearbyArea;
use App\Models\Service;
use App\Models\ServiceLocationPage;

/**
 * One URL = one @graph JSON-LD document with dynamic GEO entities.
 */
class UnifiedJsonLdGraphBuilder
{
    public function __construct(
        private readonly GeoBusinessContextResolver $geoContext,
        private readonly ConversationalAeoFaqBuilder $aeoFaqs,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function buildServiceGraph(Service $service): array
    {
        $service->loadMissing(['seo', 'faqs', 'schema', 'pincodes', 'locationPages.pincode', 'subServices.seo', 'subServices.faqs']);
        $canonical = $service->seo?->canonical_url ?: $service->publicUrl();
        $ctx = $this->geoContext->resolve($service, null);
        $siteUrl = $ctx['site_url'];

        $graph = [
            $this->medicalOrganizationNode($ctx, $siteUrl),
            $this->medicalBusinessNode($ctx, $siteUrl.'/#medicalbusiness', $canonical),
        ];

        $serviceNode = $service->toServiceSchema();
        $serviceNode['@id'] = $canonical.'#service';
        $serviceNode['provider'] = ['@id' => $siteUrl.'/#medicalbusiness'];
        $serviceNode['mainEntityOfPage'] = $canonical;

        $locationRefs = $service->locationPages
            ->filter(fn (ServiceLocationPage $m) => $m->isPubliclyIndexable())
            ->take(8)
            ->map(fn (ServiceLocationPage $m): array => [
                '@type' => 'Place',
                '@id' => $m->publicUrl().'#place',
                'name' => app(\App\Services\Operations\ServiceLocationPageProvisioner::class)
                    ->locationTitle($service, $m->pincode),
                'url' => $m->publicUrl(),
            ])
            ->values()
            ->all();

        if ($locationRefs !== []) {
            $serviceNode['hasOfferCatalog'] = [
                '@type' => 'OfferCatalog',
                'name' => $service->title.' — '.__('Locations'),
                'itemListElement' => $locationRefs,
            ];
        }

        $subServiceRefs = $service->subServices
            ->filter(fn (\App\Models\SubService $sub): bool => $sub->isListedPublicly())
            ->take(12)
            ->map(fn (\App\Models\SubService $sub): array => $sub->toSchemaFragment($canonical))
            ->values()
            ->all();

        if ($subServiceRefs !== []) {
            $serviceNode['hasPart'] = $subServiceRefs;
        }

        $graph[] = $serviceNode;

        $faqs = $this->aeoFaqs->forService($service);
        if ($faqs !== []) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $canonical.'#faq',
                'mainEntity' => $faqs,
            ];
        }

        $graph[] = $this->breadcrumbList($canonical, [
            [1, __('Home'), $siteUrl.'/'],
            [2, __('Services'), $siteUrl.'/services-catalog'],
            [3, $service->title, $canonical],
        ]);

        return [
            '@context' => 'https://schema.org',
            '@graph' => array_values($graph),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function buildLocationGraph(Service $service, PinCode $pin, ServiceLocationPage $mapping): array
    {
        $service->loadMissing(['seo', 'faqs']);
        $pin->loadMissing(['landmarks', 'hospitals', 'locationFaqs', 'nearbyAreas', 'geoLocation']);
        $canonical = $mapping->publicUrl();
        $ctx = $this->geoContext->resolve($service, $pin);
        $siteUrl = $ctx['site_url'];
        $area = $pin->area_name ?: $pin->locality ?: $pin->city ?: $pin->pincode;
        $title = app(\App\Services\Operations\ServiceLocationPageProvisioner::class)->locationTitle($service, $pin);

        $graph = [
            $this->medicalOrganizationNode($ctx, $siteUrl),
            $this->medicalBusinessNode($ctx, $canonical.'#medicalbusiness', $canonical, $area),
        ];

        $serviceNode = $service->toServiceSchema();
        $serviceNode['@id'] = $canonical.'#service';
        $serviceNode['name'] = $title;
        $serviceNode['url'] = $canonical;
        $serviceNode['provider'] = ['@id' => $canonical.'#medicalbusiness'];
        $serviceNode['areaServed'] = ['@id' => $canonical.'#geographic-area'];
        $graph[] = $serviceNode;

        $graph[] = $this->geographicAreaNode($canonical, $pin, $area);
        $graph[] = $this->serviceLocationNode($canonical, $pin, $area);

        foreach ($pin->nearbyAreas as $i => $nearby) {
            $graph[] = $this->nearbyAreaNode($canonical, $nearby, $i);
        }

        foreach ($pin->hospitals as $i => $hospital) {
            $graph[] = $this->hospitalNode($canonical, $hospital, $i);
        }

        foreach ($pin->landmarks as $i => $landmark) {
            $graph[] = $this->landmarkNode($canonical, $landmark, $i);
        }

        $graph[] = $this->availableChannelNode($canonical, $ctx);

        $faqs = $this->aeoFaqs->forLocation($service, $pin);
        if ($faqs !== []) {
            $graph[] = [
                '@type' => 'FAQPage',
                '@id' => $canonical.'#faq',
                'mainEntity' => $faqs,
            ];
        }

        $graph[] = $this->breadcrumbList($canonical, [
            [1, __('Home'), $siteUrl.'/'],
            [2, __('Services'), $siteUrl.'/services-catalog'],
            [3, $service->title, $service->publicUrl()],
            [4, $title, $canonical],
        ]);

        return [
            '@context' => 'https://schema.org',
            '@graph' => array_values($graph),
        ];
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    private function medicalOrganizationNode(array $ctx, string $siteUrl): array
    {
        return array_filter([
            '@type' => 'Organization',
            '@id' => $siteUrl.'/#organization',
            'name' => $ctx['brand'],
            'url' => $siteUrl,
            'telephone' => $ctx['telephone'],
            'description' => $ctx['description'],
            'sameAs' => $ctx['same_as'] !== [] ? $ctx['same_as'] : null,
            'knowsAbout' => $ctx['knows_about'] !== [] ? $ctx['knows_about'] : null,
            'logo' => $ctx['seo_entity']?->logo,
        ]);
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    private function medicalBusinessNode(array $ctx, string $id, string $pageUrl, ?string $areaSuffix = null): array
    {
        $name = $areaSuffix ? $ctx['brand'].' — '.$areaSuffix : $ctx['brand'];

        return array_filter([
            '@type' => ['ProfessionalService', 'LocalBusiness'],
            '@id' => $id,
            'name' => $name,
            'url' => $pageUrl,
            'telephone' => $ctx['telephone'],
            'description' => $ctx['description'],
            'address' => $ctx['address'],
            'geo' => $ctx['geo_coordinates'],
            'areaServed' => $ctx['area_served'] !== [] ? $ctx['area_served'] : null,
            'hasOfferCatalog' => $ctx['service_catalog'] !== [] ? [
                '@type' => 'OfferCatalog',
                'name' => $ctx['brand'].' '.__('Services'),
                'itemListElement' => $ctx['service_catalog'],
            ] : null,
            'sameAs' => $ctx['same_as'] !== [] ? $ctx['same_as'] : null,
            'knowsAbout' => $ctx['knows_about'] !== [] ? $ctx['knows_about'] : null,
            'parentOrganization' => ['@id' => $ctx['site_url'].'/#organization'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function geographicAreaNode(string $canonical, PinCode $pin, string $area): array
    {
        return array_filter([
            '@type' => 'Place',
            '@id' => $canonical.'#geographic-area',
            'name' => $area,
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'addressLocality' => $pin->city,
                'addressRegion' => $pin->state,
                'addressCountry' => $pin->area_name ?: $pin->state,
            ]),
            'description' => $pin->coverage_text,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function serviceLocationNode(string $canonical, PinCode $pin, string $area): array
    {
        return [
            '@type' => 'Place',
            '@id' => $canonical.'#service-location',
            'name' => $area,
            'url' => $canonical,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function nearbyAreaNode(string $canonical, PinCodeNearbyArea $nearby, int $index): array
    {
        return [
            '@type' => 'Place',
            '@id' => $canonical.'#nearby-'.$index,
            'name' => $nearby->area_name,
            'containedInPlace' => ['@id' => $canonical.'#geographic-area'],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function hospitalNode(string $canonical, PinCodeHospital $hospital, int $index): array
    {
        return array_filter([
            '@type' => 'Hospital',
            '@id' => $canonical.'#hospital-'.$index,
            'name' => $hospital->name,
            'address' => filled($hospital->address) ? $hospital->address : null,
            'medicalSpecialty' => $hospital->specialty,
            'containedInPlace' => ['@id' => $canonical.'#geographic-area'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function landmarkNode(string $canonical, PinCodeLandmark $landmark, int $index): array
    {
        return array_filter([
            '@type' => 'Place',
            '@id' => $canonical.'#landmark-'.$index,
            'name' => $landmark->name,
            'additionalType' => $landmark->landmark_type,
            'containedInPlace' => ['@id' => $canonical.'#geographic-area'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $ctx
     * @return array<string, mixed>
     */
    private function availableChannelNode(string $canonical, array $ctx): array
    {
        $channels = [];

        if (filled($ctx['telephone'])) {
            $channels[] = [
                '@type' => 'ServiceChannel',
                'servicePhone' => $ctx['telephone'],
                'availableLanguage' => 'en',
            ];
        }

        $whatsapp = config('medca.whatsapp_url');
        if (is_string($whatsapp) && $whatsapp !== '') {
            $channels[] = [
                '@type' => 'ServiceChannel',
                'serviceUrl' => $whatsapp,
                'serviceType' => 'WhatsApp',
            ];
        }

        return [
            '@type' => 'Service',
            '@id' => $canonical.'#channels',
            'availableChannel' => $channels,
        ];
    }

    /**
     * @param  list<array{0: int, 1: string, 2: string}>  $items
     * @return array<string, mixed>
     */
    private function breadcrumbList(string $canonical, array $items): array
    {
        $elements = [];
        foreach ($items as [$position, $name, $item]) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $name,
                'item' => $item,
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            '@id' => $canonical.'#breadcrumb',
            'itemListElement' => $elements,
        ];
    }
}
