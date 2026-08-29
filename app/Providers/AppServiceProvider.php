<?php

namespace App\Providers;

use App\Contracts\WhatsAppSender;
use App\Services\WhatsApp\WhatsAppSenderManager;
use App\Support\AdminMenu;
use App\Support\AppStrings;
use App\Support\Theme;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsAppSender::class, function () {
            return (new WhatsAppSenderManager)->driver();
        });
    }

    public function boot(): void
    {
        // إجبار استخدام HTTPS في بيئة الإنتاج
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }

        RateLimiter::for('otp', function (Request $request) {
            return Limit::perMinutes(10, 5)->by(
                $request->ip().'|'.$request->input('phone', '')
            );
        });

        RateLimiter::for('ai', function (Request $request) {
            return Limit::perMinute(20)->by($request->ip());
        });

        RateLimiter::for('admin-ai-copy', function (Request $request) {
            return Limit::perMinute(10)->by($request->user()?->id ?: $request->ip());
        });

        Paginator::useBootstrapFive();

        View::share('theme', Theme::class);
        View::share('strings', AppStrings::class);

        View::composer('components.layouts.admin', function ($view) {
            $view->with('adminMenu', AdminMenu::groups());
        });
    }
}
