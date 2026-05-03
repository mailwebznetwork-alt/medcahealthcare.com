<?php

namespace App\Http\Controllers\Operations;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class OperationsHubController extends Controller
{
    /**
     * Default Operations entry: Job Portal overview.
     */
    public function __invoke(): RedirectResponse
    {
        return redirect()->route('operations.job-portal.overview');
    }
}
