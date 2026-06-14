<?php

namespace Tests\Feature;

use App\Enums\ImportApprovalStatus;
use App\Models\ImportApprovalRequest;
use App\Models\User;
use App\ModuleAccess;
use App\Policies\ImportApprovalPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterSpecWave7Test extends TestCase
{
    use RefreshDatabase;

    public function test_content_writer_gets_operations_module_defaults(): void
    {
        $grants = ModuleAccess::grantsForRole('content_writer');

        $this->assertTrue($grants[ModuleAccess::DASHBOARD]);
        $this->assertTrue($grants[ModuleAccess::OPERATIONS]);
        $this->assertTrue($grants[ModuleAccess::SITE_ARCHITECT]);
        $this->assertFalse($grants[ModuleAccess::USER_MANAGEMENT]);
    }

    public function test_medical_reviewer_can_approve_but_not_self_approve(): void
    {
        $requester = User::factory()->create(['role' => 'content_writer']);
        $reviewer = User::factory()->create(['role' => 'medical_reviewer']);
        $approval = ImportApprovalRequest::query()->create([
            'requested_by' => $requester->id,
            'status' => ImportApprovalStatus::Pending,
            'workbook' => 'services',
            'staging_path' => 'imports/staging/test.xlsx',
            'requested_at' => now(),
        ]);

        $policy = app(ImportApprovalPolicy::class);

        $this->assertTrue($policy->approve($reviewer, $approval));
        $this->assertFalse($policy->approve($requester, $approval));
        $this->assertFalse($policy->submit($reviewer));
        $this->assertTrue($policy->submit($requester));
    }

    public function test_gsc_oauth_token_exchange_persists_refresh_token(): void
    {
        config([
            'growth.google_search_console.client_id' => 'client-id',
            'growth.google_search_console.client_secret' => 'client-secret',
            'growth.google_search_console.refresh_token' => null,
        ]);

        \Illuminate\Support\Facades\Http::fake([
            'oauth2.googleapis.com/token' => \Illuminate\Support\Facades\Http::sequence()
                ->push(['refresh_token' => 'stored-refresh', 'access_token' => 'access-1'], 200)
                ->push(['access_token' => 'oauth-access-token'], 200),
            'www.googleapis.com/webmasters/v3/sites' => \Illuminate\Support\Facades\Http::response(['siteEntry' => [['siteUrl' => 'https://medcahealthcare.com/']]], 200),
        ]);

        app(\App\Services\Growth\GoogleSearchConsoleCredentialStore::class)->storeOAuthTokens([
            'refresh_token' => 'stored-refresh',
        ]);

        $result = app(\App\Services\Growth\GoogleSearchConsoleService::class)->testConnection();

        $this->assertTrue($result['configured']);
        $this->assertContains('https://medcahealthcare.com/', $result['sites']);
    }
}
