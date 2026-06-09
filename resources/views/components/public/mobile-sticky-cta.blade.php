@props(['enabled' => true])

@if ($enabled)
    @php
        use App\Support\BlockContent;

        $callHref = BlockContent::telHref();
    @endphp
    <div class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 p-3 shadow-[0_-8px_30px_rgba(15,23,42,0.12)] backdrop-blur md:hidden" data-mobile-sticky-cta>
        <div class="mx-auto flex max-w-lg items-center gap-2">
            @if ($callHref !== '')
                <a href="{{ $callHref }}" data-track="phone_click" class="flex-1 rounded-lg border border-slate-300 px-3 py-3 text-center text-xs font-semibold text-slate-900">
                    {{ BlockContent::callUsLabel() }}
                </a>
            @endif
            <x-whatsapp.link class="flex-1 justify-center px-3 py-3 text-center text-xs" :label="__('WhatsApp Us')">
                {{ __('WhatsApp Us') }}
            </x-whatsapp.link>
        </div>
    </div>
@endif
