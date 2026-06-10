<?php

use App\Models\AdminNotification;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('logs and notifies when a user password is changed through the application', function () {
    $admin = User::factory()->create(['role' => 'super_admin']);

    $this->actingAs($admin);

    $target = User::factory()->create(['role' => 'editor']);
    $target->password = Hash::make('NewSecurePass1!');
    $target->save();

    $log = \Illuminate\Support\Facades\DB::table('activity_logs')
        ->where('action', 'password_changed')
        ->where('module', 'security')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->description)->toContain('user ID '.$target->id);

    expect(AdminNotification::query()->where('recipient_user_id', $admin->id)->where('action', 'password_changed')->count())->toBe(1);
});

it('logs unauthenticated password changes as system source', function () {
    $user = User::factory()->create(['role' => 'admin']);

    $user->password = Hash::make('AnotherSecure1!');
    $user->save();

    $log = \Illuminate\Support\Facades\DB::table('activity_logs')
        ->where('action', 'password_changed')
        ->latest('id')
        ->first();

    expect($log)->not->toBeNull()
        ->and($log->description)->toContain('source=unauthenticated/system');
});
