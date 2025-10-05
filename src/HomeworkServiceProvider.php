<?php

namespace Inmanturbo\Homework;

use Illuminate\Support\ServiceProvider;
use Inmanturbo\Homework\Console\Commands\CreateWorkOsClientCommand;
use Inmanturbo\Homework\Contracts\UserResponseContract;
use Inmanturbo\Homework\Support\UserResponse;

class HomeworkServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(UserResponseContract::class, function ($app) {
            return $app->make(UserResponse::class);
        });
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/workos.php');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'homework');

        if ($this->app->runningInConsole()) {
            $this->commands([
                CreateWorkOsClientCommand::class,
            ]);
        }
    }
}
