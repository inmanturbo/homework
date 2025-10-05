<?php

use Inmanturbo\Homework\Http\Middleware\AutoApproveFirstPartyClients;

it('can instantiate the auto approve middleware', function () {
    $middleware = new AutoApproveFirstPartyClients();

    expect($middleware)->toBeInstanceOf(AutoApproveFirstPartyClients::class);
});

it('can manually register the auto approve middleware', function () {
    // Register the middleware (this is what users do in their own service providers)
    app('router')->aliasMiddleware(
        'auto-approve-first-party',
        AutoApproveFirstPartyClients::class
    );

    $middleware = app('router')->getMiddleware();

    expect($middleware)->toHaveKey('auto-approve-first-party');
    expect($middleware['auto-approve-first-party'])->toBe(AutoApproveFirstPartyClients::class);
});
