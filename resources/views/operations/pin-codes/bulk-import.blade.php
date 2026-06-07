<x-operations.workspace>
    @include('operations.bulk-import._workspace', [
        'lockedWorkbook' => 'pincodes',
        'previewRoute' => route('operations.pin-codes.bulk-import.preview'),
        'confirmRoute' => route('operations.pin-codes.bulk-import.confirm'),
        'cancelRoute' => route('operations.pin-codes.bulk-import.cancel'),
    ])
</x-operations.workspace>
