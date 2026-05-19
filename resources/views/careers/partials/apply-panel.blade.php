@php
    /** @var \App\Models\Vacancy $vacancy */
@endphp

<style>
    .mc-apply-panel {
        width: 100%;
        max-width: 24rem;
        margin: 0 auto;
    }
    .mc-apply-panel-stack {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .mc-apply-online {
        background: #fff;
        border: 1px solid #e4e2e0;
        border-radius: 0.5rem;
        padding: 1.25rem;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.06);
    }
    .mc-apply-online h2 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: #2d2d2d;
    }
    .mc-apply-online > p {
        margin: 0.5rem 0 0;
        font-size: 0.875rem;
        color: #595959;
    }
    .mc-apply-online .apply-form-wrap {
        margin-top: 1rem;
    }
    .mc-wa-apply {
        display: flex;
        gap: 1rem;
        align-items: flex-start;
        background: linear-gradient(135deg, #e8f7ec 0%, #f4fdf6 100%);
        border: 1px solid #25d366;
        border-radius: 0.5rem;
        padding: 1.25rem;
        box-shadow: 0 2px 8px rgba(37, 211, 102, 0.15);
    }
    .mc-wa-apply-icon {
        flex-shrink: 0;
        color: #25d366;
    }
    .mc-wa-apply-title {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 700;
        color: #1a1a1a;
    }
    .mc-wa-apply-text {
        margin: 0.4rem 0 0;
        font-size: 0.85rem;
        line-height: 1.5;
        color: #444;
    }
    .mc-wa-apply-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-top: 1rem;
        width: 100%;
        padding: 0.75rem 1rem;
        border-radius: 0.4rem;
        background: #25d366;
        color: #fff;
        font-weight: 700;
        font-size: 0.95rem;
        text-decoration: none;
        transition: filter 0.15s;
    }
    .mc-wa-apply-btn:hover {
        filter: brightness(1.05);
    }
</style>

<aside class="mc-apply-panel" aria-label="{{ __('Apply for this role') }}">
    <div class="mc-apply-panel-stack">
        @include('careers.partials.whatsapp-apply', ['vacancy' => $vacancy])

        <div class="mc-apply-online">
            <h2>{{ __('Apply online') }}</h2>
            <p>{{ __('Submit your details — our hiring team will review your application.') }}</p>
            <div class="apply-form-wrap">
                @include('careers.partials.apply-form', ['vacancy' => $vacancy])
            </div>
        </div>
    </div>
</aside>
