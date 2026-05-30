@extends('layouts.app')

@section('title', config('medca.brand_name').' — '.config('app.name'))

@section('content')
    @include('public.partials.near-you-services', array_merge(
        app(\App\Services\Public\PublicPagePresenter::class)->nearYouPayload(),
        is_array($nearYouPayload ?? null) ? $nearYouPayload : []
    ))
    <div id="callback" class="scroll-mt-32" tabindex="-1"></div>
    <div class="min-h-[40vh]"></div>
@endsection
