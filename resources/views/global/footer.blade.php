{{-- Structure aligned with medca-healthcare `welcome.blade.php` footer (minus dynamic footer sections). --}}
<footer class="border-t border-slate-200 bg-white py-10 md:py-12">
    <div class="mx-auto flex max-w-6xl flex-col gap-8 px-4 md:flex-row md:items-start md:justify-between md:px-6 lg:px-8">
        <div>
            <p class="text-sm font-bold text-clinical-900">{{ config('medca.brand_name') }}</p>
            <p class="mt-2 max-w-xs text-sm text-slate-600">
                {{ __('Home nursing, physiotherapy, lab tests, and post-surgical care—Bangalore.') }}
            </p>
        </div>
        <div class="text-sm text-slate-600">
            <p class="font-semibold text-slate-800">{{ __('Phone') }}</p>
            <a
                class="mt-1 block text-clinical-700 hover:underline"
                href="tel:{{ preg_replace('/\s+/', '', config('medca.phone_tel')) }}"
            >
                {{ config('medca.phone_display') }}
            </a>
        </div>
        <div class="text-sm">
            @if (Route::has('login'))
                <a href="{{ route('login') }}" class="font-medium text-slate-500 hover:text-clinical-700">{{ __('Staff login') }}</a>
            @endif
            <a href="{{ route('careers.index') }}" class="mt-2 block font-medium text-slate-500 hover:text-clinical-700">{{ __('Careers') }}</a>
        </div>
    </div>
    <p class="mt-8 text-center text-xs text-slate-400">
        © {{ date('Y') }}
        {{ config('medca.brand_name') }}. {{ __('Information on this page is for general guidance and does not replace medical advice from your doctor.') }}
        {{ __('Powered by MarkOnMinds.') }}
    </p>
</footer>
