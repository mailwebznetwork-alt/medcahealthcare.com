{{-- Site Architect → add {{service:code}} tokens in block code (not here). $services = only those codes. --}}
@include('public.services.partials.services-carousel', [
    'services' => $services,
    'sectionTitle' => __('Our clinical services'),
])
