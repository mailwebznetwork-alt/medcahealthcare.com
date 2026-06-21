@props([
    'services',
    'pinCode' => null,
    'pinCodeRecord' => null,
    'emptyMessage' => null,
])

@php
    use App\Models\PinCode;

    $pin = $pinCode instanceof PinCode
        ? $pinCode
        : ($pinCodeRecord instanceof PinCode ? $pinCodeRecord : null);

    $emptyMessage = $emptyMessage ?? __('No published services are mapped to this country yet.');
@endphp

<section {{ $attributes->merge(['class' => 'space-y-6']) }} data-location-services-detail>
    @if ($services->isEmpty())
        <p class="text-sm text-slate-600">{{ $emptyMessage }}</p>
    @else
        <div class="space-y-6">
            @foreach ($services as $service)
                <x-public.location-service-detail :service="$service" :pin-code="$pin" />
            @endforeach
        </div>

        <x-public.lead-action-bar class="pt-2" />
    @endif
</section>
