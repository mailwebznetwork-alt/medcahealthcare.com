<?php

namespace App\Http\Controllers\Operations\JobPortal;

use App\Enums\ApplicationPipelineStatus;
use App\Http\Controllers\Controller;
use App\Models\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ApplicationController extends Controller
{
    public function index(): View
    {
        $this->authorize('viewAny', Application::class);

        return view('operations.job-portal.applications.index');
    }

    public function show(Application $application): View
    {
        $this->authorize('view', $application);
        $application->load('vacancy');

        return view('operations.job-portal.applications.show', compact('application'));
    }

    public function downloadResume(Application $application): StreamedResponse
    {
        $this->authorize('view', $application);

        if (! is_string($application->resume_path) || $application->resume_path === '') {
            abort(404);
        }

        $disk = Storage::disk('local');
        if (! $disk->exists($application->resume_path)) {
            abort(404);
        }

        $extension = pathinfo($application->resume_path, PATHINFO_EXTENSION) ?: 'bin';
        $downloadName = Str::slug($application->full_name).'-resume.'.$extension;

        return $disk->download($application->resume_path, $downloadName);
    }

    public function update(Request $request, Application $application): RedirectResponse
    {
        $this->authorize('update', $application);

        $validated = $request->validate([
            'pipeline_status' => ['required', Rule::enum(ApplicationPipelineStatus::class)],
        ]);

        $application->update($validated);

        return redirect()
            ->route('operations.job-portal.applications.show', $application)
            ->with('status', 'application-updated');
    }
}
