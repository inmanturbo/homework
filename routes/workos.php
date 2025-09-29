<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inmanturbo\Homework\Http\Requests\AuthenticateRequest;
use Inmanturbo\Homework\Http\Requests\GetUserRequest;
use Inmanturbo\Homework\Http\Requests\JwksRequest;

Route::prefix('user_management')->group(function () {
    Route::get('/authorize', function (Request $request) {
        return redirect('/oauth/authorize?' . $request->getQueryString());
    });

    Route::post('/authenticate', function (AuthenticateRequest $request) {
        return $request->authenticate();
    });

    Route::post('/authenticate_with_refresh_token', function (AuthenticateRequest $request) {
        return $request->authenticate();
    });

    Route::get('/users/{userId}', function (GetUserRequest $request) {
        return $request->fetchUser();
    })->middleware('auth:api');
});

Route::prefix('sso')->group(function () {
    Route::get('/jwks/{clientId}', function (JwksRequest $request) {
        return $request->getJwks();
    });
});
