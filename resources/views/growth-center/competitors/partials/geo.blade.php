<section class="mom-card p-6">
    <h2 class="mom-section-title">{{ __('GEO') }}</h2>
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <article class="mom-card p-5">
            <h3 class="mom-section-title">{{ __('Coverage Location') }}</h3>
            <form method="post" action="{{ route('growth-center.geo.location.store') }}" class="mt-4 space-y-3">
                @csrf
                <input type="text" name="label" value="{{ old('label', $geoLocation?->label) }}" placeholder="{{ __('Label') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                <div class="grid grid-cols-2 gap-3">
                    <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude', $geoLocation?->latitude) }}" placeholder="{{ __('Latitude') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                    <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude', $geoLocation?->longitude) }}" placeholder="{{ __('Longitude') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                </div>
                <input type="number" min="1" max="200" name="radius_km" value="{{ old('radius_km', $geoLocation?->radius_km ?? 25) }}" placeholder="{{ __('Radius (km)') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Save Location') }}</button>
            </form>
        </article>

        <article class="mom-card p-5">
            <h3 class="mom-section-title">{{ __('Stats') }}</h3>
            <dl class="mom-body-text mt-4 space-y-2 text-[var(--text-secondary)]">
                <div class="flex justify-between"><dt>{{ __('Total Locations') }}</dt><dd>{{ $geoStats['total_locations'] ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt>{{ __('Active Locations') }}</dt><dd>{{ $geoStats['active_locations'] ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt>{{ __('Total Pincodes') }}</dt><dd>{{ $geoStats['total_pincodes'] ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt>{{ __('Active Pincodes') }}</dt><dd>{{ $geoStats['active_pincodes'] ?? 0 }}</dd></div>
            </dl>
        </article>
    </div>
</section>

<section class="mom-card mt-8 p-6">
    <h3 class="mom-section-title">{{ __('Pincodes') }}</h3>
    <form method="post" action="{{ route('growth-center.geo.pincode.store') }}" class="mt-4 grid grid-cols-1 gap-3 xl:grid-cols-5">
        @csrf
        <select name="geo_location_id" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
            <option value="">{{ __('No location') }}</option>
            @if($geoLocation)
                <option value="{{ $geoLocation->id }}">{{ $geoLocation->label }}</option>
            @endif
        </select>
        <input type="text" name="code" placeholder="{{ __('Pincode') }}" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
        <input type="text" name="city" placeholder="{{ __('City') }}" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
        <input type="text" name="state" placeholder="{{ __('State') }}" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
        <button type="submit" class="mom-cta-primary !px-3 !py-2 !text-[11px]">{{ __('Add Pincode') }}</button>
    </form>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[42rem] text-left text-[13px]">
            <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('Code') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('City') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('State') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                @forelse ($pincodes as $pincode)
                    <tr>
                        <td class="px-4 py-3 text-[var(--text-primary)]">{{ $pincode->code }}</td>
                        <td class="px-4 py-3">{{ $pincode->city ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $pincode->state ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $pincode->is_active ? __('Active') : __('Inactive') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No pincodes found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
