@extends('layouts.app')

@section('title', __('Services near you').' — '.config('app.name'))

@section('content')
    @include('public.partials.near-you-services', [
        'services' => $services,
        'pincode' => $pincode,
        'pinCodeRecord' => $pinCodeRecord,
        'locationRequired' => $locationRequired,
    ])
@endsection
