<x-app-layout
    :page-title="__('Notifications')"
    :welcome-line="__('Workspace changes and alerts for administrators.')"
>
    <div class="space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div class="flex items-center gap-2">
                <a
                    href="{{ route('admin.notifications.index', ['filter' => 'all']) }}"
                    @class([
                        'rounded-full border px-3 py-1.5 text-xs font-medium transition-colors',
                        $filter === 'all'
                            ? 'border-[rgba(197,160,89,0.35)] bg-[rgba(197,160,89,0.1)] text-mom-gold'
                            : 'border-[var(--border-panel-soft)] text-[var(--text-muted)] hover:text-[var(--text-secondary)]',
                    ])
                >
                    {{ __('All') }}
                </a>
                <a
                    href="{{ route('admin.notifications.index', ['filter' => 'unread']) }}"
                    @class([
                        'rounded-full border px-3 py-1.5 text-xs font-medium transition-colors',
                        $filter === 'unread'
                            ? 'border-[rgba(197,160,89,0.35)] bg-[rgba(197,160,89,0.1)] text-mom-gold'
                            : 'border-[var(--border-panel-soft)] text-[var(--text-muted)] hover:text-[var(--text-secondary)]',
                    ])
                >
                    {{ __('Unread') }}
                    @if ($unreadCount > 0)
                        <span class="ml-1 text-[var(--text-muted)]">({{ $unreadCount }})</span>
                    @endif
                </a>
            </div>

            @if ($unreadCount > 0)
                <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                    @csrf
                    <button
                        type="submit"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--border-panel-soft)] text-[var(--text-secondary)] transition-colors hover:border-[rgba(197,160,89,0.35)] hover:text-mom-gold"
                        title="{{ __('Mark all as read') }}"
                        aria-label="{{ __('Mark all as read') }}"
                    >
                        <i data-lucide="list-checks" class="h-4 w-4"></i>
                    </button>
                </form>
            @endif
        </div>

        @if (session('status') === 'notifications-read')
            <p class="rounded-mom-chrome border border-[rgba(197,160,89,0.2)] bg-[rgba(197,160,89,0.08)] px-4 py-3 text-sm text-mom-gold">
                {{ __('All notifications marked as read.') }}
            </p>
        @endif

        <div class="overflow-hidden rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)]">
            @forelse ($notifications as $notification)
                <div
                    @class([
                        'flex flex-wrap items-start justify-between gap-4 border-b border-[var(--border-panel-soft)] px-5 py-4 last:border-b-0',
                        'bg-[rgba(197,160,89,0.04)]' => $notification->read_at === null,
                    ])
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="text-sm font-semibold text-[var(--text-primary)]">{{ $notification->title }}</p>
                            <span class="rounded-full border border-[var(--border-panel-soft)] px-2 py-0.5 text-[10px] uppercase tracking-wide text-[var(--text-muted)]">
                                {{ config('notifications.module_labels.'.$notification->module, $notification->module) }}
                            </span>
                            @if ($notification->read_at === null)
                                <span class="rounded-full bg-mom-gold/20 px-2 py-0.5 text-[10px] font-medium text-mom-gold">
                                    {{ __('Unread') }}
                                </span>
                            @endif
                        </div>
                        @if ($notification->body)
                            <p class="mom-subtext mt-1 text-sm">{{ $notification->body }}</p>
                        @endif
                        <p class="mt-2 text-xs text-[var(--text-muted)]">
                            {{ $notification->created_at?->format('M j, Y g:i A') }}
                            @if ($notification->actor)
                                · {{ $notification->actor->name }}
                            @endif
                        </p>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        @if ($notification->url)
                            <a
                                href="{{ route('admin.notifications.read', $notification) }}"
                                class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--border-panel-soft)] text-mom-gold transition-colors hover:border-[rgba(197,160,89,0.35)] hover:text-[var(--text-primary)]"
                                title="{{ __('Open') }}"
                                aria-label="{{ __('Open') }}"
                            >
                                <i data-lucide="external-link" class="h-4 w-4"></i>
                            </a>
                        @endif
                        @if ($notification->read_at === null)
                            <form method="POST" action="{{ route('admin.notifications.read', $notification) }}">
                                @csrf
                                @method('PATCH')
                                <button
                                    type="submit"
                                    class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-[var(--border-panel-soft)] text-[var(--text-muted)] transition-colors hover:border-[rgba(197,160,89,0.35)] hover:text-[var(--text-secondary)]"
                                    title="{{ __('Mark read') }}"
                                    aria-label="{{ __('Mark read') }}"
                                >
                                    <i data-lucide="check" class="h-4 w-4"></i>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="px-5 py-12 text-center text-sm text-[var(--text-muted)]">
                    {{ __('No notifications to show.') }}
                </p>
            @endforelse
        </div>

        @if ($notifications->hasPages())
            <div>
                {{ $notifications->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
