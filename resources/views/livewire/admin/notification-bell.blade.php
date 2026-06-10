<div
    class="relative shrink-0"
    x-data="{ open: false }"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        @click="open = ! open"
        class="relative flex h-10 w-10 items-center justify-center rounded-full border border-[var(--border-panel-soft)] text-[var(--text-secondary)] transition-all duration-320 ease-premium hover:border-[rgba(197,160,89,0.2)] hover:text-[var(--text-primary)]"
        aria-label="{{ __('Notifications') }}"
        x-bind:aria-expanded="open"
        aria-haspopup="true"
    >
        <i data-lucide="bell" class="h-[18px] w-[18px]"></i>
        @if ($unreadCount > 0)
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-mom-gold px-1 text-[10px] font-semibold text-[#1c1616]">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-1"
        @click.outside="open = false"
        class="absolute right-0 top-[calc(100%+0.5rem)] z-50 w-[min(22rem,calc(100vw-2.5rem))] max-h-[min(28rem,70vh)] overflow-hidden rounded-mom-chrome border border-[var(--border-panel-soft)] bg-[var(--bg-card-matte)] shadow-mom-elevated ring-1 ring-[rgba(197,160,89,0.08)]"
        role="menu"
    >
        <div class="flex items-center justify-between border-b border-[var(--border-panel-soft)] px-4 py-3">
            <p class="text-sm font-semibold text-[var(--text-primary)]">{{ __('Notifications') }}</p>
            @if ($unreadCount > 0)
                <button
                    type="button"
                    wire:click="markAllRead"
                    class="flex h-8 w-8 items-center justify-center rounded-full border border-[var(--border-panel-soft)] text-mom-gold transition-colors hover:border-[rgba(197,160,89,0.35)] hover:text-[var(--text-primary)]"
                    title="{{ __('Mark all read') }}"
                    aria-label="{{ __('Mark all read') }}"
                >
                    <i data-lucide="list-checks" class="h-4 w-4"></i>
                </button>
            @endif
        </div>

        <div class="max-h-[min(24rem,60vh)] overflow-y-auto custom-scrollbar">
            @forelse ($recent as $notification)
                <div
                    @class([
                        'border-b border-[var(--border-panel-soft)] px-4 py-3 last:border-b-0',
                        'bg-[rgba(197,160,89,0.05)]' => $notification->read_at === null,
                    ])
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-[var(--text-primary)]">{{ $notification->title }}</p>
                            @if ($notification->body)
                                <p class="mom-subtext mt-1 line-clamp-2 text-[13px]">{{ $notification->body }}</p>
                            @endif
                            <p class="mt-1 text-[11px] text-[var(--text-muted)]">
                                {{ $notification->created_at?->diffForHumans() }}
                            </p>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            @if ($notification->url)
                                <a
                                    href="{{ route('admin.notifications.read', $notification) }}"
                                    class="flex h-8 w-8 items-center justify-center rounded-full border border-[var(--border-panel-soft)] text-mom-gold transition-colors hover:border-[rgba(197,160,89,0.35)] hover:text-[var(--text-primary)]"
                                    title="{{ __('Open') }}"
                                    aria-label="{{ __('Open') }}"
                                >
                                    <i data-lucide="external-link" class="h-3.5 w-3.5"></i>
                                </a>
                            @endif
                            @if ($notification->read_at === null)
                                <button
                                    type="button"
                                    wire:click="markRead({{ $notification->id }})"
                                    class="flex h-8 w-8 items-center justify-center rounded-full border border-[var(--border-panel-soft)] text-mom-gold transition-colors hover:border-[rgba(197,160,89,0.35)] hover:text-[var(--text-primary)]"
                                    title="{{ __('Mark as read') }}"
                                    aria-label="{{ __('Mark as read') }}"
                                >
                                    <i data-lucide="check" class="h-3.5 w-3.5"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p class="px-4 py-8 text-center text-sm text-[var(--text-muted)]">
                    {{ __('No notifications yet.') }}
                </p>
            @endforelse
        </div>

        <div class="border-t border-[var(--border-panel-soft)] px-4 py-2.5">
            <a
                href="{{ route('admin.notifications.index') }}"
                class="inline-flex items-center gap-1.5 text-xs font-medium text-mom-gold hover:text-[var(--text-primary)]"
                @click="open = false"
            >
                <i data-lucide="list" class="h-3.5 w-3.5"></i>
                {{ __('View all notifications') }}
            </a>
        </div>
    </div>
</div>
