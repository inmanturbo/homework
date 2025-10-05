<?php

namespace Inmanturbo\Homework;

use Illuminate\Support\ServiceProvider;

class HomeworkServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/workos.php');

        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'homework');
    }
}
