<x-operations.workspace>
    @include('operations.bulk-import._workspace', [
        'lockedWorkbook' => $lockedWorkbook ?? null,
        'previewRoute' => route('operations.bulk-import.preview'),
        'confirmRoute' => route('operations.bulk-import.confirm'),
        'cancelRoute' => route('operations.bulk-import.cancel'),
    ])
</x-operations.workspace>
