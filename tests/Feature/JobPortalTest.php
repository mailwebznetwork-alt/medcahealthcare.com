<?php

use App\Enums\VacancyVisibility;
use App\Enums\VacancyWorkflowStatus;
use App\Livewire\Modules\JobPortal;
use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use App\ModuleAccess;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

it('redirects operations entry to the job portal overview', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $this->actingAs($user)
        ->get(route('modules.operations'))
        ->assertRedirect(route('operations.job-portal.overview'));
});

it('allows operations users to open the job portal overview', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $this->actingAs($user)
        ->get(route('operations.job-portal.overview'))
        ->assertOk()
        ->assertSee(__('Create vacancy'), false);
});

it('renders published vacancies in the job portal module', function () {
    $vacancy = Vacancy::factory()->published()->create([
        'slug' => 'module-listing-test',
        'title' => 'Unique Module Listing QA Title',
    ]);

    Livewire::test(JobPortal::class)
        ->assertSee('Unique Module Listing QA Title', false)
        ->assertSee(route('careers.show', ['slug' => $vacancy->slug]), false);
});

it('does not render draft vacancies in the job portal module', function () {
    $vacancy = Vacancy::factory()->create([
        'title' => 'Draft Only Should Not Render In Module',
        'workflow_status' => VacancyWorkflowStatus::Draft,
    ]);

    Livewire::test(JobPortal::class)
        ->assertDontSee($vacancy->title, false);
});

it('lists a published public vacancy on the careers site', function () {
    $vacancy = Vacancy::factory()->published()->create([
        'slug' => 'test-role-bangalore',
        'visibility' => VacancyVisibility::Public,
        'workflow_status' => VacancyWorkflowStatus::Published,
    ]);

    $this->get(route('careers.index'))->assertOk()->assertSee($vacancy->title, false);
    $this->get(route('careers.show', ['slug' => $vacancy->slug]))->assertOk()->assertSee($vacancy->title, false);
});

it('accepts a public application', function () {
    $vacancy = Vacancy::factory()->published()->create([
        'slug' => 'apply-test-role',
    ]);

    $this->post(route('careers.apply', ['slug' => $vacancy->slug]), [
        'full_name' => 'Casey Lee',
        'email' => 'casey@example.com',
        'phone' => '9123456789',
        'source' => 'web',
    ])->assertRedirect(route('careers.show', ['slug' => $vacancy->slug]));

    expect(Application::query()->where('vacancy_id', $vacancy->id)->count())->toBe(1);
    expect(Application::query()->where('vacancy_id', $vacancy->id)->value('resume_path'))->toBeNull();
});

it('stores an optional resume with a public application', function () {
    Storage::fake('local');

    $vacancy = Vacancy::factory()->published()->create([
        'slug' => 'apply-with-resume',
    ]);

    $file = UploadedFile::fake()->create('cv.pdf', 200);

    $this->post(route('careers.apply', ['slug' => $vacancy->slug]), [
        'full_name' => 'Resume Bearer',
        'email' => 'resume@example.com',
        'phone' => '9988776655',
        'source' => 'web',
        'resume' => $file,
    ])->assertRedirect(route('careers.show', ['slug' => $vacancy->slug]));

    $path = Application::query()->where('vacancy_id', $vacancy->id)->value('resume_path');
    expect($path)->toBeString();
    Storage::disk('local')->assertExists($path);
});

it('allows operations staff to download a candidate resume', function () {
    Storage::fake('local');

    $vacancy = Vacancy::factory()->published()->create();
    $path = 'job-application-resumes/2026/01/test.pdf';
    Storage::disk('local')->put($path, '%PDF-1.4 test');

    $application = Application::factory()->create([
        'vacancy_id' => $vacancy->id,
        'resume_path' => $path,
    ]);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $this->actingAs($user)
        ->get(route('operations.job-portal.applications.resume', $application))
        ->assertOk();
});
