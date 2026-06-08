<?php

use App\Enums\PublishStatus;
use App\Models\AdminDeletionTombstone;
use App\Models\Page;
use App\Models\Service;
use App\Services\Deployment\GlobalContentVariableRepository;
use App\Services\Governance\UniversalPageRegistry;
use App\Services\Operations\CategoryMasterOrchestrator;
use App\Services\Operations\ServiceMasterOrchestrator;
use App\Services\Operations\ServiceLocationMatrixReconciler;
use App\Services\Operations\SubServiceMasterOrchestrator;
use App\Support\ServicePageOverrides;
use Illuminate\Support\Facades\Artisan;

it('protects service detail page title and content from orchestrator after admin save', function () {
    $service = Service::query()->where('service_code', 'elder-care')->first()
        ?? Service::factory()->create([
            'service_code' => 'authority-elder-care',
            'title' => 'Elder Care',
            'publish_status' => PublishStatus::Published,
            'is_active' => true,
        ]);

    $page = app(\App\Services\Operations\ServiceDetailPageProvisioner::class)->provision($service->fresh(['seo']));
    $service->forceFill(['detail_page_id' => $page->id])->save();

    $adminTitle = 'ADMIN-FINAL-TITLE-'.now()->format('His');
    $adminContent = "{{block:service-detail-hero}}\n<p>ADMIN FINAL CONTENT</p>";

    $page->update(['title' => $adminTitle, 'content' => $adminContent]);
    ServicePageOverrides::markAdminSave($page->fresh());

    app(ServiceMasterOrchestrator::class)->sync($service->fresh());
    app(ServiceLocationMatrixReconciler::class)->reconcile($service->fresh());
    Artisan::call('medca:sync-page-registry');

    $page->refresh();

    expect($page->title)->toBe($adminTitle)
        ->and($page->content)->toBe($adminContent)
        ->and(ServicePageOverrides::adminAuthorityActive($page))->toBeTrue();
});

it('immediately reflects global content database writes without stale cache', function () {
    GlobalContentVariableRepository::forgetCache();

    \App\Models\GlobalContentVariable::query()->updateOrCreate(
        ['key' => 'company_name'],
        ['value' => 'Fresh Company Name', 'label' => 'Company']
    );

    $resolved = app(GlobalContentVariableRepository::class)->resolved();

    expect($resolved['company_name'])->toBe('Fresh Company Name');
});

it('prevents launch seeder from recreating tombstoned doctor-home-visit', function () {
    AdminDeletionTombstone::record('service', 'doctor-home-visit');

    $before = Service::query()->where('service_code', 'doctor-home-visit')->count();

    $seeder = new \Database\Seeders\MedcaLaunchServicesSeeder;
    $seeder->run();

    $after = Service::query()->where('service_code', 'doctor-home-visit')->count();

    expect($before)->toBe($after);
});

it('records registry without changing protected page title', function () {
    $service = Service::query()->whereNotNull('detail_page_id')->first();
    if ($service === null) {
        $this->markTestSkipped('No service with detail page.');
    }

    $page = Page::query()->findOrFail($service->detail_page_id);
    $adminTitle = 'REGISTRY-PROOF-'.now()->format('His');
    $page->update(['title' => $adminTitle]);
    ServicePageOverrides::markAdminSave($page->fresh());

    app(UniversalPageRegistry::class)->syncAll();
    app(ServiceMasterOrchestrator::class)->sync($service->fresh());

    expect(Page::query()->find($page->id)?->title)->toBe($adminTitle);
});
