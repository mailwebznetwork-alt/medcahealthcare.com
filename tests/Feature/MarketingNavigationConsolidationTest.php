<?php

use App\Models\User;
use App\ModuleAccess;
use App\Support\AdminMetricLinks;

function marketingNavGrants(): array
{
    return collect(ModuleAccess::keys())
        ->mapWithKeys(fn (string $key) => [$key => true])
        ->all();
}

it('promotes marketing dashboard and intelligence tabs to the primary sidebar', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => marketingNavGrants(),
        'role' => 'manager',
    ]);

    $this->actingAs($user)
        ->get(route('marketing.dashboard'))
        ->assertSuccessful()
        ->assertSee('data-sidebar-module="marketing"', false)
        ->assertSee(__('Google Ads'), false)
        ->assertSee(__('Lead Intent'), false)
        ->assertSee(__('Executive'), false)
        ->assertSee(__('Reporting'), false)
        ->assertSee('href="'.route('marketing.dashboard', ['tab' => 'google-ads']).'"', false)
        ->assertSee('href="'.route('marketing.intelligence', ['tab' => 'attribution']).'"', false)
        ->assertDontSee('href="'.route('marketing.campaigns').'"', false)
        ->assertDontSee('href="'.route('marketing.attribution').'"', false)
        ->assertDontSee('href="'.route('marketing.reports').'"', false);
});

it('removes in-page marketing dashboard tab navigation', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => marketingNavGrants(),
        'role' => 'manager',
    ]);

    $this->actingAs($user)
        ->get(route('marketing.dashboard', ['tab' => 'campaigns']))
        ->assertSuccessful()
        ->assertSee(__('Campaign tracker'), false)
        ->assertDontSee('wire:click="$set(\'tab\', \'overview\')"', false)
        ->assertDontSee('wire:click="$set(\'tab\', \'google-ads\')"', false);
});

it('removes in-page marketing intelligence tab navigation', function () {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => marketingNavGrants(),
        'role' => 'manager',
    ]);

    $this->actingAs($user)
        ->get(route('marketing.intelligence', ['tab' => 'calls']))
        ->assertSuccessful()
        ->assertSee(__('Calls — Today'), false)
        ->assertDontSee('wire:click="setTab(\'executive\')"', false)
        ->assertDontSee('wire:click="setTab(\'whatsapp\')"', false);
});

it('opens each marketing dashboard section from sidebar destinations', function (string $tab, string $needle) {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => marketingNavGrants(),
        'role' => 'manager',
    ]);

    $this->actingAs($user)
        ->get(AdminMetricLinks::marketingDashboard($tab))
        ->assertSuccessful()
        ->assertSee($needle, false);
})->with([
    ['overview', 'Active users'],
    ['google-ads', 'No campaign rows'],
    ['meta', 'No insights rows'],
    ['communication', 'Manual snapshot'],
    ['campaigns', 'Campaign tracker'],
    ['insights', 'Rule-based insights'],
    ['lead-intent', 'Total lead intents'],
]);

it('opens each marketing intelligence section from sidebar destinations', function (string $tab, string $needle) {
    $user = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => marketingNavGrants(),
        'role' => 'manager',
    ]);

    $this->actingAs($user)
        ->get(AdminMetricLinks::marketingIntelligence($tab))
        ->assertSuccessful()
        ->assertSee($needle, false);
})->with([
    ['executive', 'Total Leads'],
    ['whatsapp', 'Source breakdown'],
    ['calls', 'Calls — Today'],
    ['attribution', 'First-touch attribution'],
    ['conversions', 'Conversion metrics'],
    ['reporting', 'Export leads (CSV)'],
]);

it('preserves legacy marketing routes and permissions', function () {
    $manager = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => marketingNavGrants(),
        'role' => 'manager',
    ]);

    $this->actingAs($manager)
        ->get(route('marketing.campaigns'))
        ->assertRedirect(route('marketing.dashboard').'#marketing-campaigns');

    $this->actingAs($manager)
        ->get(route('marketing.attribution'))
        ->assertRedirect(route('marketing.intelligence', ['tab' => 'attribution']));

    $viewer = User::factory()->create([
        'email_verified_at' => now(),
        'module_access' => marketingNavGrants(),
        'role' => 'viewer',
    ]);

    $this->actingAs($viewer)
        ->get(route('marketing.dashboard', ['tab' => 'insights']))
        ->assertForbidden();
});
