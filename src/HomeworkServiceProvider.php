<?php

namespace Inmanturbo\Homework;

use Illuminate\Support\ServiceProvider;
use Inmanturbo\Homework\Http\Middleware\AutoApproveFirstPartyClients;
use Laravel\Passport\Passport;

class HomeworkServiceProvider extends ServiceProvider
{
    public function register() {}

    public function boot()
    {
        $this->app['router']->aliasMiddleware('auto-approve-first-party', AutoApproveFirstPartyClients::class);

        $this->app['router']->pushMiddlewareToGroup('web', AutoApproveFirstPartyClients::class);

        $this->loadRoutesFrom(__DIR__ . '/../routes/workos.php');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'homework');

        Passport::authorizationView('homework::auth.authorize');

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../resources/views' => resource_path('views/vendor/homework'),
            ], 'homework-views');
        }
    }
}
