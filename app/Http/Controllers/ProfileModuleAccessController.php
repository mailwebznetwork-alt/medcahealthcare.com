<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateModuleAccessRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

class ProfileModuleAccessController extends Controller
{
    public function update(UpdateModuleAccessRequest $request): RedirectResponse
    {
        $request->user()->update([
            'module_access' => $request->validated('module_access'),
        ]);

        return Redirect::route('profile.edit')->with('status', 'module-access-updated');
    }
}
