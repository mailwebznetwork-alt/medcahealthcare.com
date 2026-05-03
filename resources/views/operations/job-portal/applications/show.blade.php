<x-app-layout
    :page-title="$application->full_name"
    :welcome-line="$application->vacancy?->title"
>
    @if (session('status') === 'application-updated')
        <p class="mom-body-text mb-6 text-[var(--success)]" role="status">{{ __('Pipeline stage updated.') }}</p>
    @endif

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="mom-card p-6 lg:col-span-2">
            <h2 class="mom-section-title">{{ __('Candidate') }}</h2>
            <dl class="mt-6 space-y-4">
                <div>
                    <dt class="mom-micro">{{ __('Email') }}</dt>
                    <dd class="mom-body-text mt-1"><a href="mailto:{{ $application->email }}" class="text-mom-gold hover:underline">{{ $application->email }}</a></dd>
                </div>
                <div>
                    <dt class="mom-micro">{{ __('Phone') }}</dt>
                    <dd class="mom-body-text mt-1"><a href="tel:{{ $application->phone }}" class="text-mom-gold hover:underline">{{ $application->phone }}</a></dd>
                </div>
                <div>
                    <dt class="mom-micro">{{ __('Location (candidate)') }}</dt>
                    <dd class="mom-body-text mt-1">{{ $application->city ?? '—' }} @if ($application->pin_code) · {{ $application->pin_code }} @endif</dd>
                </div>
                <div>
                    <dt class="mom-micro">{{ __('Cover message') }}</dt>
                    <dd class="mom-body-text mt-1 whitespace-pre-wrap">{{ $application->cover_message ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="mom-micro">{{ __('WhatsApp tracking') }}</dt>
                    <dd class="mom-body-text mt-1">
                        @if ($application->whatsapp_clicked_at)
                            {{ __('Click recorded at :time', ['time' => $application->whatsapp_clicked_at->timezone(config('app.timezone'))->format('Y-m-d H:i')]) }}
                        @else
                            {{ __('No WhatsApp click recorded for this submission.') }}
                        @endif
                    </dd>
                </div>
            </dl>
        </div>
        <div class="mom-card p-6">
            <h2 class="mom-section-title">{{ __('Pipeline') }}</h2>
            <form method="post" action="{{ route('operations.job-portal.applications.update', $application) }}" class="mt-6 space-y-4">
                @csrf
                @method('patch')
                <div>
                    <x-input-label for="pipeline_status" :value="__('Stage')" variant="mom" />
                    <select id="pipeline_status" name="pipeline_status" class="mt-2 block w-full rounded-mom-md border-[rgba(255,255,255,0.045)] bg-[rgba(28,22,18,0.75)] px-3 py-2.5 text-sm text-[var(--text-primary)] shadow-mom-inner">
                        @foreach (\App\Enums\ApplicationPipelineStatus::cases() as $st)
                            <option value="{{ $st->value }}" @selected($application->pipeline_status->value === $st->value)>{{ $st->label() }}</option>
                        @endforeach
                    </select>
                    <x-input-error class="mt-2" :messages="$errors->get('pipeline_status')" variant="mom" />
                </div>
                <x-primary-button variant="mom" type="submit">{{ __('Save stage') }}</x-primary-button>
            </form>
            <div class="mt-8 border-t border-[rgba(255,255,255,0.045)] pt-6">
                <a href="{{ route('operations.job-portal.applications.index') }}" class="text-[var(--text-muted)] hover:text-[var(--text-secondary)]">{{ __('← Back to applications') }}</a>
            </div>
        </div>
    </div>
</x-app-layout>
