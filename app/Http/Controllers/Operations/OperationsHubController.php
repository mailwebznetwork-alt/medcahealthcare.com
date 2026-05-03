<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class OperationsHubController extends Controller
{
    /**
     * Landing workspace for Operations; Job Portal lives in the right-hand rail tabs.
     */
    public function __invoke(): View
    {
        return view('operations.hub');
    }
}
