<x-operations.workspace>
    @include('operations.bulk-import._workspace', [
        'lockedWorkbook' => 'services',
        'previewRoute' => route('operations.services.bulk-import.preview'),
        'confirmRoute' => route('operations.services.bulk-import.confirm'),
        'cancelRoute' => route('operations.services.bulk-import.cancel'),
    ])
</x-operations.workspace>
