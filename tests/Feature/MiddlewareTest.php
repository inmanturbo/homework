<?php

use Inmanturbo\Homework\Http\Middleware\AutoApproveFirstPartyClients;

it('can instantiate the auto approve middleware', function () {
    $middleware = new AutoApproveFirstPartyClients();

    expect($middleware)->toBeInstanceOf(AutoApproveFirstPartyClients::class);
});

it('registers the auto approve middleware', function () {
    $middleware = app('router')->getMiddleware();

    expect($middleware)->toHaveKey('auto-approve-first-party');
    expect($middleware['auto-approve-first-party'])->toBe(AutoApproveFirstPartyClients::class);
});
