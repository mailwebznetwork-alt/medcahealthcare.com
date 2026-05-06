@extends('layouts.app')

@section('title', config('medca.brand_name').' — '.config('app.name'))

@section('content')
    {{-- scroll-mt-32 (~8rem) aligns with sticky topbar + navbar stack for anchor clearance --}}
    <div id="callback" class="scroll-mt-32" tabindex="-1"></div>
    <div class="min-h-[52vh]"></div>
@endsection
