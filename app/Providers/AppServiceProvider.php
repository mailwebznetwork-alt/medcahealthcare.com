<?php

namespace App\Providers;

use App\Contracts\Deployment\AiDeploymentAdvisoryInterface;
use App\Http\Controllers\Public\LeadCaptureController;
use App\Models\AdminNotification;
use App\Models\Block;
use App\Models\Blog;
use App\Models\BusinessProfile;
use App\Models\Competitor;
use App\Models\CompetitorTracking;
use App\Models\Lead;
use App\Models\MarketingCampaign;
use App\Models\MarketingSetting;
use App\Models\Media;
use App\Models\Module;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Review;
use App\Models\SeoEntity;
use App\Models\SeoTechnical;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\SubService;
use App\Models\ServiceLocationPage;
use App\Models\SiteKeywordRanking;
use App\Models\ThemeConfiguration;
use App\Models\User;
use App\Observers\BlogObserver;
use App\Observers\CompetitorTrackingObserver;
use App\Observers\LeadObserver;
use App\Observers\PageObserver;
use App\Observers\PinCodeObserver;
use App\Observers\ServiceCategoryObserver;
use App\Observers\SubServiceObserver;
use App\Observers\ServiceLocationPageObserver;
use App\Observers\ServiceObserver;
use App\Observers\SiteKeywordRankingObserver;
use App\Policies\AdminNotificationPolicy;
use App\Policies\BlockPolicy;
use App\Policies\BlogPolicy;
use App\Policies\CompetitorPolicy;
use App\Policies\LeadPolicy;
use App\Policies\MarketingCampaignPolicy;
use App\Policies\MarketingSettingPolicy;
use App\Policies\MediaPolicy;
use App\Policies\ModulePolicy;
use App\Policies\PagePolicy;
use App\Policies\PinCodePolicy;
use App\Policies\ReviewPolicy;
use App\Policies\ServiceCategoryPolicy;
use App\Policies\ServicePolicy;
use App\Policies\SubServicePolicy;
use App\Policies\ThemeConfigurationPolicy;
use App\Policies\UserPolicy;
use App\Services\Content\ContentRenderContext;
use App\Services\Content\ServiceBindingRegistry;
use App\Services\Deployment\NullAiDeploymentAdvisory;
use App\Services\Import\ImportRegistry;
use App\Services\Import\PinCodeEntityImporter;
use App\Services\Integrations\WhatsAppClickToChatService;
use App\Services\ServiceContextCollector;
use App\Services\Theme\ThemeResolver;
use Illuminate\Auth\Events\Login;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ServiceContextCollector::class);
        $this->app->singleton(ContentRenderContext::class);
        $this->app->singleton(ServiceBindingRegistry::class);
        $this->app->singleton(\App\Services\Content\ServiceBindingResolver::class);
        $this->app->singleton(\App\Services\Content\BlockBoundServicesResolver::class);

        $this->app->bind(
            AiDeploymentAdvisoryInterface::class,
            NullAiDeploymentAdvisory::class
        );

        $this->app->singleton(WhatsAppClickToChatService::class);

        $this->app->singleton(\App\Services\Import\ImportBatchRecorder::class);
        $this->app->singleton(\App\Services\Import\ServiceImportDefaults::class);
        $this->app->singleton(\App\Services\Import\WorkbookImportContext::class);
        $this->app->singleton(\App\Services\Import\ImportSideEffectsGate::class);

        $this->app->singleton(ImportRegistry::class, function (): ImportRegistry {
            $registry = new ImportRegistry;
            $registry->register('categories', \App\Services\Import\CategoryEntityImporter::class);
            $registry->register('services', \App\Services\Import\ServiceEntityImporter::class);
            $registry->register('sub_services', \App\Services\Import\SubServiceEntityImporter::class);
            $registry->register('pincodes', \App\Services\Import\PinCodeEntityImporter::class);
            $registry->register('geo', \App\Services\Import\GeoEnrichmentEntityImporter::class);
            $registry->register('mappings', \App\Services\Import\MappingEntityImporter::class);

            return $registry;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->ensurePublicLeadRouteIsRegistered();

        $this->configureRateLimiting();

        Blade::precompiler(function (string $value): string {
            return preg_replace_callback(
                '/\{\{\s*module:([\w-]+)\s*\}\}/',
                function (array $matches): string {
                    $key = $matches[1];

                    return '{!! \\'.Livewire::class.'::mount(config("modules.'.$key.'")) !!}';
                },
                $value
            ) ?? $value;
        });

        Blade::directive('module', function (?string $expression): string {
            $expression = trim($expression ?? '');

            if ($expression === '') {
                return '<?php ?>';
            }

            return "<?php
                \$__moduleKey = {$expression};
                if (is_string(\$__moduleKey) && \$__moduleKey !== '' && (\$__moduleClass = config('modules.'.\$__moduleKey))) {
                    echo \\Livewire\\Livewire::mount(\$__moduleClass);
                }
            ?>";
        });

        Gate::policy(AdminNotification::class, AdminNotificationPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(PinCode::class, PinCodePolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(SubService::class, SubServicePolicy::class);
        Gate::policy(ServiceCategory::class, ServiceCategoryPolicy::class);
        Gate::policy(Block::class, BlockPolicy::class);
        Gate::policy(Blog::class, BlogPolicy::class);
        Gate::policy(Module::class, ModulePolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(Page::class, PagePolicy::class);
        Gate::policy(ThemeConfiguration::class, ThemeConfigurationPolicy::class);
        Gate::policy(MarketingSetting::class, MarketingSettingPolicy::class);
        Gate::policy(MarketingCampaign::class, MarketingCampaignPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Competitor::class, CompetitorPolicy::class);
        Gate::policy(Review::class, ReviewPolicy::class);

        Page::observe(PageObserver::class);
        Blog::observe(BlogObserver::class);
        Service::observe(ServiceObserver::class);
        ServiceCategory::observe(ServiceCategoryObserver::class);
        SubService::observe(SubServiceObserver::class);
        PinCode::observe(PinCodeObserver::class);
        ServiceLocationPage::observe(ServiceLocationPageObserver::class);
        CompetitorTracking::observe(CompetitorTrackingObserver::class);
        SiteKeywordRanking::observe(SiteKeywordRankingObserver::class);
        Lead::observe(LeadObserver::class);

        View::composer('layouts.app', function ($view): void {
            $view->with('marketingSettings', MarketingSetting::current());

            $globalSiteSeo = [
                'entity' => null,
                'technical' => null,
                'business' => null,
            ];

            if (Schema::hasTable('business_profiles')) {
                $businessProfile = BusinessProfile::query()->where('website', config('app.url'))->first()
                    ?? BusinessProfile::query()->latest('id')->first();

                $globalSiteSeo['business'] = $businessProfile;

                if ($businessProfile instanceof BusinessProfile) {
                    if (Schema::hasTable('seo_entities')) {
                        $globalSiteSeo['entity'] = SeoEntity::query()
                            ->where('business_profile_id', $businessProfile->id)
                            ->first();
                    }

                    if (Schema::hasTable('seo_technical')) {
                        $globalSiteSeo['technical'] = SeoTechnical::query()
                            ->where('business_profile_id', $businessProfile->id)
                            ->first();
                    }
                }
            }

            $view->with('globalSiteSeo', $globalSiteSeo);

            $view->with('serviceContextCollector', app(ServiceContextCollector::class));

            $whatsApp = app(WhatsAppClickToChatService::class);
            $view->with('whatsAppNumbers', $whatsApp->activeNumbers());
            $view->with('whatsAppPrimaryUrl', $whatsApp->primaryUrl());
            $view->with('whatsAppFloatingEnabled', $whatsApp->isFloatingButtonEnabled());
        });

        View::composer(['global.header', 'global.footer', 'global.floating'], function ($view): void {
            $view->with('marketingSettings', MarketingSetting::current());

            if (Schema::hasTable('theme_configurations')) {
                $view->with('themeBranding', app(ThemeResolver::class)->branding());
            }

            $whatsApp = app(WhatsAppClickToChatService::class);
            $view->with('whatsAppNumbers', $whatsApp->activeNumbers());
            $view->with('whatsAppPrimaryUrl', $whatsApp->primaryUrl());
            $view->with('whatsAppFloatingEnabled', $whatsApp->isFloatingButtonEnabled());
        });

        Paginator::useTailwind();

        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user;
            if ($user instanceof User) {
                $user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }

    /**
     * ContentParser renders blocks via Blade::render(); ensure the lead route exists
     * even if route cache or load order omits the web.php registration.
     */
    private function ensurePublicLeadRouteIsRegistered(): void
    {
        $this->app->booted(function (): void {
            if (Route::has('public.leads.store')) {
                return;
            }

            Route::post('/leads', [LeadCaptureController::class, 'store'])
                ->middleware('throttle:public_leads')
                ->name('public.leads.store');
        });
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('api_leads', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('public_leads', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('payments_notify', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('marketing_clicks', function (Request $request) {
            $limit = (int) config('marketing_automation.click_tracking.rate_limit_per_minute', 120);

            return Limit::perMinute($limit)->by($request->ip());
        });
    }
}
