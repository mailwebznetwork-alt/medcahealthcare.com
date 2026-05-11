<?php

namespace App\Http\Controllers\Careers;

use App\Enums\ApplicationPipelineStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Careers\StoreJobApplicationRequest;
use App\Models\Application;
use App\Models\Vacancy;
use App\Services\Integrations\OutboundWebhookDispatcher;
use App\Support\JobPostingSchema;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CareersController extends Controller
{
    public function index(): View
    {
        $vacancies = Vacancy::query()->careersListing()->paginate(12);

        return view('careers.index', compact('vacancies'));
    }

    public function show(string $slug): View
    {
        $vacancy = Vacancy::query()->careersListing()->where('slug', $slug)->firstOrFail();
        $schema = JobPostingSchema::forVacancy($vacancy);

        return view('careers.show', compact('vacancy', 'schema'));
    }

    public function storeApplication(StoreJobApplicationRequest $request, string $slug): RedirectResponse
    {
        $vacancy = Vacancy::query()->careersListing()->where('slug', $slug)->firstOrFail();

        $data = $request->validated();
        $whatsappClick = (bool) ($data['whatsapp_click'] ?? false);
        unset($data['whatsapp_click'], $data['resume']);

        $resumePath = null;
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store(
                'job-application-resumes/'.now()->format('Y/m'),
                'local'
            );
        }

        $application = Application::query()->create([
            'vacancy_id' => $vacancy->id,
            'full_name' => $data['full_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'pin_code' => $data['pin_code'] ?? null,
            'city' => $data['city'] ?? null,
            'cover_message' => $data['cover_message'] ?? null,
            'resume_path' => $resumePath,
            'source' => $whatsappClick ? 'whatsapp' : ($data['source'] ?? 'web'),
            'whatsapp_clicked_at' => $whatsappClick ? now() : null,
            'pipeline_status' => ApplicationPipelineStatus::Applied,
        ]);

        app(OutboundWebhookDispatcher::class)->dispatch('job.application.submitted', [
            'application_id' => $application->id,
            'vacancy_id' => $vacancy->id,
            'vacancy_slug' => $vacancy->slug,
            'email_domain' => Str::after((string) $application->email, '@'),
        ]);

        return redirect()
            ->route('careers.show', ['slug' => $slug])
            ->with('status', 'application-received');
    }
}
