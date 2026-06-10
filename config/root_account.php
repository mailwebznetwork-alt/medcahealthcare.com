<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Root super administrator email
    |--------------------------------------------------------------------------
    |
    | This account is permanently protected: permissions stay full, the account
    | cannot be deleted or deactivated, and no other user may modify its access.
    | Set ROOT_SUPERADMIN_EMAIL in .env to the real mailbox for the root operator.
    |
    */
    'email' => strtolower((string) env('ROOT_SUPERADMIN_EMAIL', 'wdjerrie@markonminds.test')),

];
