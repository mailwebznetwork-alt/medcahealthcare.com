<x-app-layout
    :page-title="__('User Management')"
    :welcome-line="__('Directory, access, and operational identity in one place.')"
>
    @if (session('status') === 'user-created')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('User created.') }}</p>
    @elseif (session('status') === 'user-updated')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('User updated.') }}</p>
    @elseif (session('status') === 'user-deleted')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('User removed.') }}</p>
    @elseif (session('status') === 'user-activated')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('User activated.') }}</p>
    @elseif (session('status') === 'user-deactivated')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('User deactivated.') }}</p>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-4">
        <form method="get" action="{{ route('user-management.index') }}" class="flex flex-1 flex-wrap items-end gap-3">
            <div class="min-w-[12rem] flex-1">
                <label class="mom-micro mb-1 block" for="user-q">{{ __('Search') }}</label>
                <x-text-input
                    id="user-q"
                    name="q"
                    type="search"
                    class="w-full"
                    :value="request('q')"
                    placeholder="{{ __('Name, email, phone, role…') }}"
                    variant="mom"
                />
            </div>
            <div class="min-w-[10rem]">
                <label class="mom-micro mb-1 block" for="user-status">{{ __('Status') }}</label>
                <select
                    id="user-status"
                    name="status"
                    class="w-full rounded-mom-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner"
                    onchange="this.form.submit()"
                >
                    <option value="all" @selected(request('status', 'all') === 'all')>{{ __('All') }}</option>
                    <option value="active" @selected(request('status') === 'active')>{{ __('Active') }}</option>
                    <option value="inactive" @selected(request('status') === 'inactive')>{{ __('Inactive') }}</option>
                </select>
            </div>
            <div class="min-w-[10rem]">
                <label class="mom-micro mb-1 block" for="user-role">{{ __('Role label') }}</label>
                <select
                    id="user-role"
                    name="role"
                    class="w-full rounded-mom-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner"
                    onchange="this.form.submit()"
                >
                    <option value="">{{ __('All roles') }}</option>
                    @foreach ($roleLabels as $rl)
                        <option value="{{ $rl }}" @selected(request('role') === $rl)>{{ $rl }}</option>
                    @endforeach
                </select>
            </div>
            <x-secondary-button variant="mom" type="submit">{{ __('Apply') }}</x-secondary-button>
        </form>

        @can('create', \App\Models\User::class)
            <a
                href="{{ route('user-management.create') }}"
                class="mom-cta-primary"
            >{{ __('New user') }}</a>
        @endcan
    </div>

    <div class="mom-table mt-8 overflow-hidden rounded-mom-lg border border-[rgba(255,255,255,0.045)]">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[44rem] text-left text-[13px]">
                <thead class="bg-[var(--bg-card-table-head)] text-[11px] font-semibold uppercase tracking-[0.12em] text-[var(--text-muted)]">
                    <tr>
                        <th class="px-4 py-3 font-medium">{{ __('User') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Email') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Role') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Status') }}</th>
                        <th class="px-4 py-3 font-medium">{{ __('Last login') }}</th>
                        <th class="w-[1%] whitespace-nowrap px-4 py-3 font-medium">{{ __('Access') }}</th>
                        <th class="w-[1%] whitespace-nowrap px-4 py-3 font-medium text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[rgba(255,255,255,0.045)] text-[var(--text-secondary)]">
                    @forelse ($users as $row)
                        @php
                            $accessIcons = $row->enabledModuleAccessIcons();
                        @endphp
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    @if ($row->profile_image_path)
                                        <img
                                            src="{{ $row->profileImageUrl() }}"
                                            alt=""
                                            class="h-9 w-9 shrink-0 rounded-full border border-[rgba(255,255,255,0.06)] object-cover"
                                        />
                                    @else
                                        <span
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-[rgba(255,255,255,0.06)] bg-[rgba(255,255,255,0.04)] text-[11px] font-semibold text-mom-gold"
                                            aria-hidden="true"
                                        >{{ mb_strtoupper(mb_substr($row->name, 0, 1)) }}</span>
                                    @endif
                                    <div class="min-w-0">
                                        <p class="font-medium text-[var(--text-primary)]">{{ $row->name }}</p>
                                        @if ($row->isRootSuperAdmin())
                                            <p class="mom-micro mt-0.5 text-mom-gold">{{ __('Protected root account') }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-[12px]">{{ $row->email }}</td>
                            <td class="px-4 py-3">{{ $row->role_label ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-2">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $row->is_active ? 'bg-[var(--success)]' : 'bg-[var(--danger)]' }}"></span>
                                    {{ $row->is_active ? __('Active') : __('Inactive') }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-[var(--text-muted)]">
                                {{ $row->last_login_at?->timezone(config('app.timezone'))->format('Y-m-d H:i') ?? __('Never') }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="sr-only">{{ $row->moduleAccessSummary() }}</span>
                                <div class="flex flex-wrap items-center gap-2.5" aria-hidden="true">
                                    @foreach ($accessIcons as $icon)
                                        <i data-lucide="{{ $icon }}" class="h-4 w-4 shrink-0 text-[var(--text-secondary)]"></i>
                                    @endforeach
                                    @if ($accessIcons === [])
                                        <span class="text-[var(--text-muted)]">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @canany(['update', 'changeActiveState', 'delete'], $row)
                                    <div class="flex items-center justify-end gap-1" role="group" aria-label="{{ __('User actions') }}">
                                        @can('update', $row)
                                            <a
                                                href="{{ route('user-management.edit', $row) }}"
                                                class="inline-flex h-8 w-8 items-center justify-center rounded-mom-sm text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]"
                                                aria-label="{{ __('Edit') }}"
                                            >
                                                <i data-lucide="pencil" class="h-4 w-4 shrink-0 text-current"></i>
                                            </a>
                                        @endcan
                                        @can('changeActiveState', $row)
                                            @if ($row->is_active)
                                                <form method="post" action="{{ route('user-management.deactivate', $row) }}" class="inline-flex">
                                                    @csrf
                                                    @method('patch')
                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-mom-sm text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]"
                                                        aria-label="{{ __('Deactivate') }}"
                                                    >
                                                        <i data-lucide="user-minus" class="h-4 w-4 shrink-0 text-current"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="post" action="{{ route('user-management.activate', $row) }}" class="inline-flex">
                                                    @csrf
                                                    @method('patch')
                                                    <button
                                                        type="submit"
                                                        class="inline-flex h-8 w-8 items-center justify-center rounded-mom-sm text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[var(--bg-hover)] hover:text-[var(--text-primary)]"
                                                        aria-label="{{ __('Activate') }}"
                                                    >
                                                        <i data-lucide="user-plus" class="h-4 w-4 shrink-0 text-current"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @endcan
                                        @can('delete', $row)
                                            <form
                                                method="post"
                                                action="{{ route('user-management.destroy', $row) }}"
                                                class="inline-flex"
                                                onsubmit="return confirm('{{ __('Delete this user permanently?') }}');"
                                            >
                                                @csrf
                                                @method('delete')
                                                <button
                                                    type="submit"
                                                    class="inline-flex h-8 w-8 items-center justify-center rounded-mom-sm text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:bg-[rgba(220,38,38,0.08)] hover:text-[var(--danger)]"
                                                    aria-label="{{ __('Delete') }}"
                                                >
                                                    <i data-lucide="trash-2" class="h-4 w-4 shrink-0 text-current"></i>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                @else
                                    <span class="text-[var(--text-muted)]">—</span>
                                @endcanany
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-[var(--text-muted)]">
                                {{ __('No users match your filters.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $users->links() }}
    </div>
</x-app-layout>
