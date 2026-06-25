@php
    /** @var \App\Models\Vacancy $vacancy */
@endphp

@if (session('status') === 'application-received')
    <p class="text-sm font-medium text-emerald-700" role="status">{{ __('Thank you — your application was received.') }}</p>
@endif

<form method="post" action="{{ route('careers.apply', ['slug' => $vacancy->slug]) }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    <div>
        <x-input-label for="full_name" :value="__('Full name')" variant="public" />
        <x-text-input id="full_name" name="full_name" type="text" class="mt-2 block w-full" :value="old('full_name')" required variant="public" />
        <x-input-error class="mt-2" :messages="$errors->get('full_name')" variant="public" />
    </div>
    <div>
        <x-input-label for="email" :value="__('Email')" variant="public" />
        <x-text-input id="email" name="email" type="email" class="mt-2 block w-full" :value="old('email')" required variant="public" />
        <x-input-error class="mt-2" :messages="$errors->get('email')" variant="public" />
    </div>
    <div>
        <x-input-label for="phone" :value="__('Phone')" variant="public" />
        <x-text-input id="phone" name="phone" type="tel" class="mt-2 block w-full" :value="old('phone')" required variant="public" />
        <x-input-error class="mt-2" :messages="$errors->get('phone')" variant="public" />
    </div>
    <div>
        <x-input-label for="city" :value="__('City (optional)')" variant="public" />
        <x-text-input id="city" name="city" type="text" class="mt-2 block w-full" :value="old('city')" variant="public" />
    </div>
    <div>
        <x-input-label for="country" :value="__('Country Name (optional)')" variant="public" />
        <x-text-input id="country" name="country" type="text" class="mt-2 block w-full" :value="old('country')" variant="public" />
    </div>
    <div>
        <x-input-label for="cover_message" :value="__('Message')" variant="public" />
        <textarea id="cover_message" name="cover_message" rows="4" class="mt-2 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 shadow-sm placeholder:text-slate-400 focus:border-medca-primary focus:ring-1 focus:ring-medca-primary">{{ old('cover_message') }}</textarea>
    </div>
    <div>
        <x-input-label for="resume" :value="__('Resume (optional)')" variant="public" />
        <input
            id="resume"
            name="resume"
            type="file"
            accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
            class="mt-2 block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-sm file:font-medium file:text-slate-800 hover:file:bg-slate-200"
        />
        <p class="mt-1 text-sm text-slate-500">{{ __('PDF or Word, up to 5 MB.') }}</p>
        <x-input-error class="mt-2" :messages="$errors->get('resume')" variant="public" />
    </div>
    <input type="hidden" name="source" value="web" />
    <label class="flex items-start gap-2 text-[13px] text-slate-600">
        <input type="checkbox" name="whatsapp_click" value="1" class="mt-1 h-4 w-4 rounded border-slate-300 text-medca-primary focus:ring-medca-primary" @checked(old('whatsapp_click')) />
        <span>{{ __('I reached this role through a WhatsApp link') }}</span>
    </label>
    <x-primary-button variant="public" type="submit">{{ __('Submit application') }}</x-primary-button>
</form>
