<x-site-architect.workspace :page-title="__('Module Manager')" :welcome-line="__('Define custom data modules and manage their records without code changes.')">
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h2 class="mom-section-title">{{ __('Module Manager') }}</h2>
            <p class="mom-subtext mt-2 max-w-2xl">{{ __('Create Products, testimonials, or any custom collection. Each module gets its own database table and admin CRUD screens.') }}</p>
        </div>
        @can('create', \App\Models\Module::class)
            <x-admin.link-button href="{{ route('site-architect.modules.create') }}">{{ __('Create new module') }}</x-admin.link-button>
        @endcan
    </div>

    @if (session('status') === 'module-deleted')
        <p class="mom-body-text mt-6 text-[var(--success)]" role="status">{{ __('Module deleted.') }}</p>
    @endif

    <x-admin.card class="mt-8" flush>
        @if ($modules->isEmpty())
            <div class="p-10 text-center">
                <p class="mom-section-title">{{ __('No custom modules yet') }}</p>
                <p class="mom-subtext mt-2">{{ __('Start by defining fields for a module such as Products or Team Members.') }}</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-[color:var(--border-tabstrip-divider)]">
                    <thead class="bg-[rgba(255,255,255,0.02)]">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ __('Module') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ __('Table') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ __('Fields') }}</th>
                            <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ __('Status') }}</th>
                            <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-[var(--text-muted)]">{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[color:var(--border-tabstrip-divider)]">
                        @foreach ($modules as $module)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-semibold text-[var(--text-primary)]">{{ $module->name }}</p>
                                    <p class="mom-subtext text-xs">{{ $module->slug }}</p>
                                </td>
                                <td class="px-5 py-4 font-mono text-xs text-[var(--text-secondary)]">{{ $module->table_name }}</td>
                                <td class="px-5 py-4 text-sm text-[var(--text-secondary)]">{{ $module->field_definitions_count }}</td>
                                <td class="px-5 py-4">
                                    @if ($module->is_active)
                                        <span class="rounded-full bg-[rgba(34,197,94,0.12)] px-2.5 py-1 text-xs font-semibold text-emerald-400">{{ __('Active') }}</span>
                                    @else
                                        <span class="rounded-full bg-[rgba(148,163,184,0.12)] px-2.5 py-1 text-xs font-semibold text-slate-400">{{ __('Inactive') }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        @can('manageRecords', $module)
                                            <a href="{{ route('site-architect.modules.records.index', $module) }}" class="mom-cta-ghost text-xs">{{ __('Records') }}</a>
                                        @endcan
                                        @can('update', $module)
                                            <a href="{{ route('site-architect.modules.edit', $module) }}" class="mom-cta-ghost text-xs">{{ __('Edit schema') }}</a>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-admin.card>
</x-site-architect.workspace>
