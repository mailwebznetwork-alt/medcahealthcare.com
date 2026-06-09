@extends('layouts.app')

@section('title', __('Categories near you').' — '.config('medca.brand_name'))

@section('content')
    @include('public.partials.near-you-services', [
        'categories' => $categories,
        'pincode' => $pincode,
        'pinCodeRecord' => $pinCodeRecord,
        'locationRequired' => $locationRequired,
    ])
@endsection
