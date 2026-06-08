<?php

use App\Livewire\Operations\JobPortal\ApplicationsIndex;
use App\Livewire\Operations\JobPortal\VacanciesIndex;
use App\Livewire\Operations\ServiceCategories\Index as ServiceCategoriesIndex;
use App\Livewire\Operations\Services\Index as ServicesIndex;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Models\Vacancy;
use App\ModuleAccess;
use Livewire\Livewire;

function operationsBulkUser(): User
{
    return User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);
}

it('renders bulk selection controls on the services list', function () {
    $user = operationsBulkUser();
    Service::factory()->count(2)->create();

    $this->actingAs($user)
        ->get(route('operations.services.index'))
        ->assertOk()
        ->assertSee(__('Select all visible'), false)
        ->assertSee(__('Select row'), false);

    Livewire::actingAs($user)
        ->test(ServicesIndex::class)
        ->call('toggleBulkRow', Service::query()->value('id'))
        ->assertSee(__('Modify selected'), false)
        ->assertSee(__('Duplicate selected'), false)
        ->assertSee(__('Delete Selected'), false);
});

it('bulk deletes selected services from the list', function () {
    $user = operationsBulkUser();
    $keep = Service::factory()->create(['title' => 'Keep Service']);
    $remove = Service::factory()->create(['title' => 'Remove Service']);

    Livewire::actingAs($user)
        ->test(ServicesIndex::class)
        ->call('toggleBulkRow', $remove->id)
        ->call('openBulkAction', 'delete')
        ->set('bulkDeleteConfirmText', 'DELETE')
        ->call('confirmBulkAction')
        ->assertHasNoErrors();

    expect(Service::query()->whereKey($keep->id)->exists())->toBeTrue()
        ->and(Service::query()->whereKey($remove->id)->exists())->toBeFalse();
});

it('bulk duplicates selected services from the list', function () {
    $user = operationsBulkUser();
    $original = Service::factory()->create(['title' => 'Original Service']);
    $before = Service::query()->count();

    Livewire::actingAs($user)
        ->test(ServicesIndex::class)
        ->call('toggleBulkRow', $original->id)
        ->call('openBulkAction', 'duplicate')
        ->call('confirmBulkAction')
        ->assertHasNoErrors();

    expect(Service::query()->count())->toBe($before + 1);
});

it('redirects to edit when modifying a single selected service', function () {
    $user = operationsBulkUser();
    $service = Service::factory()->create();

    Livewire::actingAs($user)
        ->test(ServicesIndex::class)
        ->call('toggleBulkRow', $service->id)
        ->call('openBulkModify')
        ->assertRedirect(route('operations.services.edit', $service));
});

it('renders bulk selection controls on service categories list', function () {
    $user = operationsBulkUser();
    ServiceCategory::factory()->count(2)->create();

    $this->actingAs($user)
        ->get(route('operations.service-categories.index'))
        ->assertOk()
        ->assertSee(__('Select all visible'), false);

    Livewire::actingAs($user)
        ->test(ServiceCategoriesIndex::class)
        ->call('toggleBulkRow', ServiceCategory::query()->value('id'))
        ->assertSee(__('Modify selected'), false)
        ->assertSee(__('Duplicate selected'), false);
});

it('bulk deletes selected service categories from the list', function () {
    $user = operationsBulkUser();
    $keep = ServiceCategory::factory()->create(['name' => 'Keep Cat']);
    $remove = ServiceCategory::factory()->create(['name' => 'Remove Cat']);

    Livewire::actingAs($user)
        ->test(ServiceCategoriesIndex::class)
        ->call('toggleBulkRow', $remove->id)
        ->call('openBulkAction', 'delete')
        ->set('bulkDeleteConfirmText', 'DELETE')
        ->call('confirmBulkAction')
        ->assertHasNoErrors();

    expect(ServiceCategory::query()->whereKey($keep->id)->exists())->toBeTrue()
        ->and(ServiceCategory::query()->whereKey($remove->id)->exists())->toBeFalse();
});

it('renders bulk selection controls on vacancies list', function () {
    $user = operationsBulkUser();
    Vacancy::factory()->count(2)->create();

    $this->actingAs($user)
        ->get(route('operations.job-portal.vacancies.index'))
        ->assertOk()
        ->assertSee(__('Select all visible'), false);

    Livewire::actingAs($user)
        ->test(VacanciesIndex::class)
        ->call('toggleBulkRow', Vacancy::query()->value('id'))
        ->assertSee(__('Modify selected'), false)
        ->assertSee(__('Duplicate selected'), false);
});

it('bulk duplicates selected vacancies from the list', function () {
    $user = operationsBulkUser();
    $original = Vacancy::factory()->create(['title' => 'Nurse Role']);
    $before = Vacancy::query()->count();

    Livewire::actingAs($user)
        ->test(VacanciesIndex::class)
        ->call('toggleBulkRow', $original->id)
        ->call('openBulkAction', 'duplicate')
        ->call('confirmBulkAction')
        ->assertHasNoErrors();

    expect(Vacancy::query()->count())->toBe($before + 1);
});

it('shows modify-only bulk toolbar on applications list', function () {
    $user = operationsBulkUser();
    $vacancy = Vacancy::factory()->create();
    \App\Models\Application::factory()->create(['vacancy_id' => $vacancy->id]);

    $this->actingAs($user)
        ->get(route('operations.job-portal.applications.index'))
        ->assertOk()
        ->assertSee(__('Select all visible'), false);

    Livewire::actingAs($user)
        ->test(ApplicationsIndex::class)
        ->call('toggleBulkRow', \App\Models\Application::query()->value('id'))
        ->assertSee(__('Open selected'), false)
        ->assertDontSee(__('Duplicate selected'), false)
        ->assertDontSee(__('Delete Selected'), false);
});

it('redirects to application show when modifying a single selection', function () {
    $user = operationsBulkUser();
    $vacancy = Vacancy::factory()->create();
    $application = \App\Models\Application::factory()->create(['vacancy_id' => $vacancy->id]);

    Livewire::actingAs($user)
        ->test(ApplicationsIndex::class)
        ->call('toggleBulkRow', $application->id)
        ->call('openBulkModify')
        ->assertRedirect(route('operations.job-portal.applications.show', $application));
});
