@extends('layouts.app')

@section('title', config('medca.brand_name').' — '.config('app.name'))

@section('content')
    {{-- scroll-mt-28 (~7rem) aligns with config('medca.marketing_sticky_header_approx_px') for sticky header clearance --}}
    <div id="callback" class="scroll-mt-28" tabindex="-1"></div>
    <div class="min-h-[52vh]"></div>
@endsection
