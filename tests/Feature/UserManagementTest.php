<?php

it('has user management routes registered', function () {
    $routes = collect(app('router')->getRoutes())->map(function ($route) {
        return $route->uri();
    });

    expect($routes->contains('user_management/authenticate'))->toBeTrue();
    expect($routes->contains('user_management/authenticate_with_refresh_token'))->toBeTrue();
    expect($routes->contains('user_management/users/{userId}'))->toBeTrue();
});

it('has sso jwks route registered', function () {
    $routes = collect(app('router')->getRoutes())->map(function ($route) {
        return $route->uri();
    });

    expect($routes->contains('sso/jwks/{clientId}'))->toBeTrue();
});