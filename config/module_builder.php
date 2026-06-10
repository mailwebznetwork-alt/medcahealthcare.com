<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Dynamic module schema managers
    |--------------------------------------------------------------------------
    |
    | Comma-separated display names (case-insensitive) allowed to define or
    | change custom field schemas (Label, field name, type, required). All
    | other users may only enter values on existing fields.
    |
    */
    'schema_manager_names' => array_values(array_filter(array_unique(array_map(
        static fn (string $n): string => strtolower(trim($n)),
        explode(',', (string) env('MODULE_BUILDER_SCHEMA_MANAGER_NAMES', 'MOMJERRIE'))
    )))),

];
