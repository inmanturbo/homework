<?php

namespace Inmanturbo\Homework;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Inmanturbo\Homework\Http\Middleware\AutoApproveFirstPartyClients;
use Laravel\Fortify\Fortify;
use Laravel\Passport\Passport;
use WorkOS\WorkOS as WorkOSSDK;

class HomeworkServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Register middleware
        $this->app['router']->aliasMiddleware('auto-approve-first-party', AutoApproveFirstPartyClients::class);

        $this->app['router']->pushMiddlewareToGroup('web', AutoApproveFirstPartyClients::class);

        Route::group([], function () {
            require __DIR__ . '/../routes/workos.php';
        });

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'workos-passport');

        Passport::authorizationView('workos-passport::auth.authorize');

        $this->configureWorkOSSDK();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../routes/workos.php' => base_path('routes/workos.php'),
            ], 'workos-routes');

            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/workos-passport'),
            ], 'workos-views');
        }
    }

    protected function configureWorkOSSDK()
    {
        $baseUrl = config('services.workos.base_url');

        if ($baseUrl && $baseUrl !== 'https://api.workos.com') {
            WorkOSSDK::setApiBaseUrl($baseUrl);
        }
    }
}
