<?php

use Inmanturbo\Homework\HomeworkServiceProvider;

it('can instantiate the service provider', function () {
    $serviceProvider = new HomeworkServiceProvider(app());

    expect($serviceProvider)->toBeInstanceOf(HomeworkServiceProvider::class);
});

it('registers the service provider', function () {
    expect(app()->getProviders(HomeworkServiceProvider::class))->not->toBeEmpty();
});
