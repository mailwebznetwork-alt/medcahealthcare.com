<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class SourceOfTruthController extends Controller
{
    public function index(): View
    {
        return view('system.source-of-truth');
    }
}
