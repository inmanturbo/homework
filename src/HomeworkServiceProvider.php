<?php

namespace Inmanturbo\Homework;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Passport;
use Laravel\Fortify\Fortify;
use WorkOS\WorkOS as WorkOSSDK;
use Inmanturbo\Homework\Http\Middleware\AutoApproveFirstPartyClients;

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

        // Apply middleware to the OAuth authorize route
        $this->app['router']->pushMiddlewareToGroup('web', AutoApproveFirstPartyClients::class);

        // Load routes without default middleware
        Route::group([], function () {
            require __DIR__.'/../routes/workos.php';
        });

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'workos-passport');

        // Configure Passport views
        Passport::authorizationView('workos-passport::auth.authorize');

        // Configure Fortify to use our login view for OAuth flows
        Fortify::loginView(function () {
            return view('workos-passport::auth.login');
        });

        // Configure WorkOS SDK to use local base URL
        $this->configureWorkOSSDK();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../routes/workos.php' => base_path('routes/workos.php'),
            ], 'workos-routes');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/workos-passport'),
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