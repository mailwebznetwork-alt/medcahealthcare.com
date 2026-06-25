@extends('layouts.app')

@section('title', __('Services').' — '.config('medca.brand_name'))

@section('content')
    @include('public.partials.near-you-services', [
        'categories' => $categories,
        'pinCodeRecord' => $pinCodeRecord,
        'locationRequired' => $locationRequired,
    ])
@endsection
