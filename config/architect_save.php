<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Architect save bypass operators
    |--------------------------------------------------------------------------
    |
    | Users matching these names (case-insensitive) or the root super-admin email
    | may save with empty required fields after acknowledging a warning.
    |
    */
    'bypass_operator_names' => array_values(array_filter(array_unique(array_map(
        static fn (string $n): string => strtolower(trim($n)),
        explode(',', (string) env('ARCHITECT_SAVE_BYPASS_NAMES', 'MOMJERRIE'))
    )))),

];
