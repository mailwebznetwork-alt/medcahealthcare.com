<?php

use App\Models\AdminNotification;
use App\Models\User;
use App\Services\ActivityLogService;

it('fans out admin notifications to other admins when activity is logged', function () {
    $actor = User::factory()->create(['role' => 'admin']);
    $otherAdmin = User::factory()->create(['role' => 'admin']);
    $superAdmin = User::factory()->create(['role' => 'super_admin']);
    User::factory()->create(['role' => 'editor']);

    $this->actingAs($actor);

    app(ActivityLogService::class)->log(
        'user_create',
        'user_management',
        'Created user ID 99.'
    );

    expect(AdminNotification::query()->where('recipient_user_id', $otherAdmin->id)->count())->toBe(1)
        ->and(AdminNotification::query()->where('recipient_user_id', $superAdmin->id)->count())->toBe(1)
        ->and(AdminNotification::query()->where('recipient_user_id', $actor->id)->count())->toBe(0);

    $notification = AdminNotification::query()->where('recipient_user_id', $otherAdmin->id)->first();

    expect($notification)->not->toBeNull()
        ->and($notification->title)->toBe('User added')
        ->and($notification->module)->toBe('user_management')
        ->and($notification->actor_user_id)->toBe($actor->id);
});

it('does not fan out muted activity actions', function () {
    $actor = User::factory()->create(['role' => 'admin']);
    User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($actor);

    app(ActivityLogService::class)->log('login_success', 'auth', 'Successful login for test@example.com.');

    expect(AdminNotification::query()->count())->toBe(0);
});

it('shows the notification bell for admins', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertSeeLivewire('admin.notification-bell');
});

it('hides the notification bell for non-admin roles', function () {
    $editor = User::factory()->create(['role' => 'editor']);

    $this->actingAs($editor)
        ->get(route('dashboard'))
        ->assertSuccessful()
        ->assertDontSeeLivewire('admin.notification-bell');
});

it('allows admins to open the notifications index', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $actor = User::factory()->create(['role' => 'super_admin']);

    AdminNotification::query()->create([
        'recipient_user_id' => $admin->id,
        'module' => 'operations',
        'action' => 'service_category.created',
        'entity_type' => 'service_category',
        'title' => 'Service category added',
        'body' => 'Lab Tests (lab-tests)',
        'url' => '/operations',
        'actor_user_id' => $actor->id,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.notifications.index'))
        ->assertSuccessful()
        ->assertSee('Service category added');
});

it('marks a notification as read', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $notification = AdminNotification::query()->create([
        'recipient_user_id' => $admin->id,
        'module' => 'user_management',
        'action' => 'user_update',
        'title' => 'User updated',
        'body' => 'Updated user ID 5.',
        'url' => null,
        'actor_user_id' => null,
    ]);

    $this->actingAs($admin)
        ->patch(route('admin.notifications.read', $notification))
        ->assertRedirect();

    expect($notification->fresh()->read_at)->not->toBeNull();
});
