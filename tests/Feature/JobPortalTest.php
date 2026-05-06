<?php

use App\Enums\VacancyVisibility;
use App\Enums\VacancyWorkflowStatus;
use App\Models\Application;
use App\Models\User;
use App\Models\Vacancy;
use App\ModuleAccess;

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
});
