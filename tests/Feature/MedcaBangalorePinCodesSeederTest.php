<?php

use App\Models\PinCode;
use App\Services\UserLocationService;
use Database\Seeders\MedcaBangalorePinCodesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds arekere default pincode as serviceable', function () {
    $this->seed(MedcaBangalorePinCodesSeeder::class);

    expect(PinCode::query()->where('pincode', '560076')->where('is_active', true)->exists())->toBeTrue()
        ->and(app(UserLocationService::class)->setManualPincode('560076'))->toBe('560076');
});
