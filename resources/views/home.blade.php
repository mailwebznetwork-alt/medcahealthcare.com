@extends('layouts.app')

@section('title', config('medca.brand_name').' — '.config('app.name'))

@section('content')
    <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6 lg:px-8">
        <p class="mom-micro">{{ config('medca.top_bar_claim') }}</p>
        <h1 class="mom-title-page mt-4 max-w-2xl">{{ config('medca.brand_name') }}</h1>
        <p class="mom-subtext mt-4 max-w-2xl">{{ config('medca.tagline') }}</p>
        <p class="mom-body-text mt-8 max-w-2xl text-[var(--text-secondary)]">
            {{ __('Premium healthcare services across Bengaluru. Explore services, locations, and careers.') }}
        </p>
    </div>
@endsection
