@extends('layouts.app')

@section('title', __('Categories by country').' — '.config('medca.brand_name'))

@section('content')
    @include('public.partials.near-you-services', [
        'categories' => $categories,
        'country' => $pincode,
        'pinCodeRecord' => $pinCodeRecord,
        'locationRequired' => $locationRequired,
    ])
@endsection
