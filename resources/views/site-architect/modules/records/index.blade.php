@php
    /** @var \App\Models\Module $module */
    /** @var \Illuminate\Contracts\Pagination\LengthAwarePaginator $recordRows */
    /** @var \Illuminate\Support\Collection $indexFields */
@endphp

<x-site-architect.workspace :page-title="$module->name" :welcome-line="__('Manage records for the :module module.', ['module' => $module->name])">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="mom-section-title">{{ $module->name }}</h2>
            <p class="mom-subtext mt-2 font-mono text-xs">{{ $module->table_name }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('site-architect.modules.index') }}" class="mom-cta-ghost">{{ __('All modules') }}</a>
            @can('update', $module)
                <a href="{{ route('site-architect.modules.edit', $module) }}" class="mom-cta-ghost">{{ __('Edit schema') }}</a>
            @endcan
            @can('manageRecords', $module)
                <x-admin.link-button href="{{ route('site-architect.modules.records.create', $module) }}">{{ __('Add record') }}</x-admin.link-button>
            @endcan
        </div>
    </div>

    @if (session('status') === 'record-created')
        <p class="mom-body-text mt-6 text-[var(--success)]" role="status">{{ __('Record created.') }}</p>
    @endif
    @if (session('status') === 'record-updated')
        <p class="mom-body-text mt-6 text-[var(--success)]" role="status">{{ __('Record updated.') }}</p>
    @endif
    @if (session('status') === 'record-deleted')
        <p class="mom-body-text mt-6 text-[var(--success)]" role="status">{{ __('Record deleted.') }}</p>
    @endif
    @if (session('status') === 'module-created')
        <p class="mom-body-text mt-6 text-[var(--success)]" role="status">{{ __('Module created.') }}</p>
    @endif

    <x-admin.card class="mt-8" flush>
        @if ($recordRows->isEmpty())
            <div class="p-10 text-center">
                <p class="mom-section-title">{{ __('No records yet') }}</p>
                <p class="mom-subtext mt-2">{{ __('Add your first :module entry.', ['module' => strtolower($module->name)]) }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[color:var(--border-tabstrip-divider)]">
                    <thead class="bg-[rgba(255,255,255,0.02)]">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">ID</th>
                            @foreach ($indexFields as $field)
                                <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ $field->label }}</th>
                            @endforeach
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)]">
                        @foreach ($recordRows as $row)
                            <tr>
                                <td class="px-5 py-4 text-sm text-[var(--text-secondary)]">{{ $row->id }}</td>
                                @foreach ($indexFields as $field)
                                    <td class="px-5 py-4 text-sm text-[var(--text-primary)]">
                                        @php $value = $row->{$field->field_name} ?? null; @endphp
                                        @if ($field->field_type === \App\Models\FieldDefinition::TYPE_BOOLEAN)
                                            {{ $value ? __('Yes') : __('No') }}
                                        @else
                                            {{ \Illuminate\Support\Str::limit((string) $value, 80) }}
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-5 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('site-architect.modules.records.edit', [$module, $row->id]) }}" class="mom-cta-ghost text-xs">{{ __('Edit') }}</a>
                                        <form method="post" action="{{ route('site-architect.modules.records.destroy', [$module, $row->id]) }}" onsubmit="return confirm(@js(__('Delete this record?')))">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-semibold text-[var(--danger)] hover:underline">{{ __('Delete') }}</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($recordRows->hasPages())
                <div class="border-t border-[color:var(--border-tabstrip-divider)] px-5 py-4">
                    {{ $recordRows->links() }}
                </div>
            @endif
        @endif
    </x-admin.card>
</x-site-architect.workspace>
