@extends('layouts.admin')

@section('title', __('Bulk Import'))

@section('content')
<div class="p-6">
    @include('operations.bulk-import._workspace', [
        'lockedWorkbook' => $lockedWorkbook ?? null,
        'previewRoute' => route('operations.bulk-import.preview'),
        'confirmRoute' => route('operations.bulk-import.confirm'),
        'cancelRoute' => route('operations.bulk-import.cancel'),
    ])
</div>
@endsection
