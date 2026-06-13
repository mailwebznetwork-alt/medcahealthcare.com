<?php

use App\Models\User;
use App\ModuleAccess;

it('shows services bulk import in operations toolbar', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'role' => 'manager',
        'module_access' => collect(ModuleAccess::keys())
            ->mapWithKeys(fn (string $k) => [$k => $k === ModuleAccess::OPERATIONS])
            ->all(),
    ]);

    $this->actingAs($user)
        ->get(route('operations.services.index'))
        ->assertOk()
        ->assertSee(__('Bulk import'), false);

    $this->actingAs($user)
        ->get(route('operations.services.bulk-import'))
        ->assertOk()
        ->assertSee('services.xlsx', false)
        ->assertSee(__('Export live data'), false);

    $this->actingAs($user)
        ->get(route('operations.services.bulk-import.export.download', 'services'))
        ->assertOk()
        ->assertDownload('services.xlsx');
});
