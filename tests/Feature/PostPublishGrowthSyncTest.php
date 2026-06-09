<?php

use App\Models\Page;
use App\Models\User;
use App\Support\PostPublishGrowthSync;
use Illuminate\Support\Facades\Bus;

it('defers growth sync until after the response', function () {
    Bus::fake();

    PostPublishGrowthSync::defer();

    Bus::assertNothingDispatched();
});

it('saves a site architect page without dispatching ai pulse inline', function () {
    Bus::fake();

    $user = User::factory()->create(['role' => 'super_admin']);
    $this->actingAs($user);

    $page = Page::factory()->create([
        'title' => 'Save Test Page',
        'slug' => 'save-test-page-'.uniqid(),
        'content' => '{{block:hero-home}}',
        'is_active' => true,
    ]);

    $page->update(['meta_title' => 'Updated title']);

    expect($page->fresh()->meta_title)->toBe('Updated title');
    Bus::assertNothingDispatched();
});
