<?php

namespace App\Providers;

use App\Models\Blog;
use App\Models\Page;
use App\Models\PinCode;
use App\Models\Service;
use App\Models\User;
use App\Policies\BlogPolicy;
use App\Policies\PagePolicy;
use App\Policies\PinCodePolicy;
use App\Policies\ServicePolicy;
use App\Policies\UserPolicy;
use Illuminate\Auth\Events\Login;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Blog::class, BlogPolicy::class);
        Gate::policy(Page::class, PagePolicy::class);

        Paginator::useTailwind();

        Event::listen(Login::class, function (Login $event): void {
            $user = $event->user;
            if ($user instanceof User) {
                $user->forceFill(['last_login_at' => now()])->saveQuietly();
            }
        });
    }
}
