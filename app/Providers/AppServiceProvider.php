<?php

namespace App\Providers;

use App\Models\Block;
use App\Models\Blog;
use App\Models\BusinessProfile;
use App\Models\Lead;
use App\Models\MarketingCampaign;
use App\Models\MarketingSetting;
use App\Models\Media;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\SeoEntity;
use App\Models\SeoTechnical;
use App\Models\Service;
use App\Models\User;
use App\Policies\BlockPolicy;
use App\Policies\BlogPolicy;
use App\Policies\LeadPolicy;
use App\Policies\MarketingCampaignPolicy;
use App\Policies\MarketingSettingPolicy;
use App\Policies\MediaPolicy;
use App\Policies\PagePolicy;
use App\Policies\PinCodePolicy;
use App\Policies\ServicePolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
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

        Gate::policy(User::class, UserPolicy::class);
        Gate::policy(PinCode::class, PinCodePolicy::class);
        Gate::policy(Service::class, ServicePolicy::class);
        Gate::policy(Block::class, BlockPolicy::class);
        Gate::policy(Blog::class, BlogPolicy::class);
        Gate::policy(Media::class, MediaPolicy::class);
        Gate::policy(Page::class, PagePolicy::class);
        Gate::policy(MarketingSetting::class, MarketingSettingPolicy::class);
        Gate::policy(MarketingCampaign::class, MarketingCampaignPolicy::class);
        Gate::policy(Lead::class, LeadPolicy::class);

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
        });

        Paginator::useTailwind();

        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user;
            if ($user instanceof User) {
                $user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }
}
