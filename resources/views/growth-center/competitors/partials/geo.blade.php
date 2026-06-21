{{-- Location & coverage (embedded under Growth Center SEO tab) --}}
<article id="growth-geo-coverage" class="mom-card p-5">
    <h3 class="mom-section-title">{{ __('LOCATION & COVERAGE') }}</h3>
    <p class="mom-subtext mt-2">{{ __('Set service radius with latitude and longitude (e.g. from Google Maps share, without embedding a map here).') }}</p>
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div>
            <h4 class="mom-micro text-mom-gold">{{ __('Coverage center') }}</h4>
            <form method="post" action="{{ route('growth-center.geo.location.store') }}" class="mt-4 space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <input type="number" step="0.0000001" name="latitude" value="{{ old('latitude', $geoLocation?->latitude) }}" placeholder="{{ __('Latitude') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                    <input type="number" step="0.0000001" name="longitude" value="{{ old('longitude', $geoLocation?->longitude) }}" placeholder="{{ __('Longitude') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                </div>
                <input type="number" min="1" max="200" name="radius_km" value="{{ old('radius_km', $geoLocation?->radius_km ?? 25) }}" placeholder="{{ __('Radius (km)') }}" class="w-full rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
                <button type="submit" class="mom-cta-primary mom-cta-compact">{{ __('Save coverage center') }}</button>
            </form>
        </div>

        <div>
            <h4 class="mom-micro text-mom-gold">{{ __('Coverage stats') }}</h4>
            <dl class="mom-body-text mt-4 space-y-2 text-[var(--text-secondary)]">
                <div class="flex justify-between"><dt>{{ __('Total Locations') }}</dt><dd>{{ $geoStats['total_locations'] ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt>{{ __('Total Countries') }}</dt><dd>{{ $geoStats['total_pincodes'] ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt>{{ __('Serviceable Countries') }}</dt><dd>{{ $geoStats['serviceable_pincodes'] ?? 0 }}</dd></div>
                <div class="flex justify-between"><dt>{{ __('High Priority Countries') }}</dt><dd>{{ $geoStats['high_priority_pincodes'] ?? 0 }}</dd></div>
            </dl>
        </div>
    </div>
</article>

<article class="mom-card p-5">
    <h3 class="mom-section-title">{{ __('Countries & landing paths') }}</h3>
    <form method="post" action="{{ route('growth-center.geo.country.store') }}" class="mt-4 grid grid-cols-1 gap-3 xl:grid-cols-5">
        @csrf
        <select name="geo_location_id" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
            <option value="">{{ __('No location') }}</option>
            @if($geoLocation)
                <option value="{{ $geoLocation->id }}">{{ __('Primary coverage location') }}</option>
            @endif
        </select>
        <input type="text" name="country" placeholder="{{ __('Country') }}" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]" required>
        <input type="text" name="landing_page" placeholder="{{ __('Landing page path') }}" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
        <select name="priority" class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
            <option value="high">high</option>
            <option value="medium" selected>medium</option>
            <option value="low">low</option>
        </select>
        <label class="inline-flex items-center gap-2 rounded-mom-chrome border border-[rgba(255,255,255,0.06)] bg-[rgba(28,22,18,0.75)] px-3 py-2 text-sm text-[var(--text-primary)]">
            <input type="hidden" name="serviceable" value="0">
            <input type="checkbox" name="serviceable" value="1" checked class="rounded border-[rgba(255,255,255,0.12)] bg-transparent text-[var(--success)]">
            <span>{{ __('Serviceable') }}</span>
        </label>
        <button type="submit" class="mom-cta-primary mom-cta-compact">{{ __('Add Country') }}</button>
    </form>
    <div class="mt-4 overflow-x-auto">
        <table class="w-full min-w-[42rem] text-left text-[13px]">
            <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                <tr>
                    <th class="px-4 py-3 font-medium">{{ __('Country') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Landing Page') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Priority') }}</th>
                    <th class="px-4 py-3 font-medium">{{ __('Serviceable') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                @forelse ($pincodes as $pincode)
                    <tr>
                        <td class="px-4 py-3 text-[var(--text-primary)]">{{ $pincode->pincode }}</td>
                        <td class="px-4 py-3">{{ $pincode->landing_page ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $pincode->priority ?? 'medium' }}</td>
                        <td class="px-4 py-3">{{ $pincode->serviceable ? __('Yes') : __('No') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-8 text-center text-[var(--text-muted)]">{{ __('No pincodes found.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</article>
