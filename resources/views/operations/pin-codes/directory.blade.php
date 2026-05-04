<x-operations.workspace>
    <h2 class="mom-section-title mb-8">{{ __('Directory') }}</h2>

    @if (session('status') === 'pin-code-created')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Pin code created.') }}</p>
    @endif
    @if (session('status') === 'pin-code-updated')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Pin code updated.') }}</p>
    @endif
    @if (session('status') === 'pin-code-deleted')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Pin code removed.') }}</p>
    @endif
    @if (session('status') === 'pin-code-activated')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Pin code activated.') }}</p>
    @endif
    @if (session('status') === 'pin-code-deactivated')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Pin code deactivated.') }}</p>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-4">
        <form method="get" action="{{ route('operations.pin-codes.directory') }}" class="flex flex-1 flex-wrap gap-3">
            <x-text-input name="q" type="search" class="min-w-[12rem] flex-1" :value="request('q')" placeholder="{{ __('Search pincode, area, city, locality…') }}" variant="mom" />
            <select name="city" class="rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('All cities') }}</option>
                @foreach ($cities as $c)
                    <option value="{{ $c }}" @selected(request('city') === $c)>{{ $c }}</option>
                @endforeach
            </select>
            <select name="serviceable" class="rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('Serviceability') }}</option>
                <option value="1" @selected(request('serviceable') === '1')>{{ __('Serviceable') }}</option>
                <option value="0" @selected(request('serviceable') === '0')>{{ __('Not serviceable') }}</option>
            </select>
            <select name="active" class="rounded-mom-chrome border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                <option value="">{{ __('Status') }}</option>
                <option value="1" @selected(request('active') === '1')>{{ __('Active') }}</option>
                <option value="0" @selected(request('active') === '0')>{{ __('Inactive') }}</option>
            </select>
            <x-secondary-button variant="mom" type="submit">{{ __('Filter') }}</x-secondary-button>
        </form>
        @can('create', \App\Models\PinCode::class)
            <a href="{{ route('operations.pin-codes.create') }}" class="mom-cta-primary">{{ __('Add pin code') }}</a>
        @endcan
    </div>

    <div class="mom-card mt-8 overflow-hidden p-0">
        @if ($pinCodes->isEmpty())
            <div class="p-10 text-center">
                <p class="mom-section-title">{{ __('No pin codes match your filters') }}</p>
                <p class="mom-subtext mt-2">{{ __('Add locations to build your operational coverage map and local SEO dataset.') }}</p>
                @can('create', \App\Models\PinCode::class)
                    <a href="{{ route('operations.pin-codes.create') }}" class="mom-subtext mt-6 inline-flex text-mom-gold hover:underline">{{ __('Add your first pin code') }}</a>
                @endcan
            </div>
        @else
            <div class="mom-table overflow-x-auto">
                <table class="w-full min-w-[960px] text-left text-[13px]">
                    <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                        <tr>
                            <th class="px-4 py-3 font-medium">{{ __('Pincode') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Area') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('City') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Locality') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Service') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Active') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Charge') }}</th>
                            <th class="px-4 py-3 font-medium">{{ __('Slug') }}</th>
                            <th class="px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)] text-[var(--text-secondary)]">
                        @foreach ($pinCodes as $row)
                            <tr>
                                <td class="px-4 py-3 font-medium text-[var(--text-primary)]">{{ $row->pincode }}</td>
                                <td class="px-4 py-3">{{ $row->area_name }}</td>
                                <td class="px-4 py-3">{{ $row->city }}</td>
                                <td class="px-4 py-3">{{ $row->locality ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-mom-chrome border border-[rgba(255,255,255,0.06)] px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-[var(--text-secondary)]">
                                        {{ $row->is_serviceable ? __('Yes') : __('No') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($row->is_active)
                                        <span class="text-[var(--success)]">{{ __('On') }}</span>
                                    @else
                                        <span class="text-[var(--text-muted)]">{{ __('Off') }}</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($row->delivery_charge !== null)
                                        {{ number_format((float) $row->delivery_charge, 2) }}
                                    @else
                                        <span class="mom-micro">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="max-w-[10rem] truncate font-mono text-[12px]" title="{{ $row->slug }}">{{ $row->slug }}</span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                                        @can('update', $row)
                                            <a href="{{ route('operations.pin-codes.edit', $row) }}" class="text-mom-gold hover:underline">{{ __('Edit') }}</a>
                                        @endcan
                                        @can('changeActiveState', $row)
                                            @if ($row->is_active)
                                                <form method="post" action="{{ route('operations.pin-codes.deactivate', $row) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-[var(--text-muted)] hover:text-[var(--text-primary)]">{{ __('Deactivate') }}</button>
                                                </form>
                                            @else
                                                <form method="post" action="{{ route('operations.pin-codes.activate', $row) }}" class="inline">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="text-[var(--text-muted)] hover:text-[var(--text-primary)]">{{ __('Activate') }}</button>
                                                </form>
                                            @endif
                                        @endcan
                                        @can('delete', $row)
                                            <form method="post" action="{{ route('operations.pin-codes.destroy', $row) }}" class="inline" onsubmit="return confirm(@js(__('Delete this pin code?')));">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-[var(--danger)] hover:underline">{{ __('Delete') }}</button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mom-backend-hairline-t px-4 py-3">
                {{ $pinCodes->links() }}
            </div>
        @endif
    </div>
</x-operations.workspace>
