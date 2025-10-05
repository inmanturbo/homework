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

Route::post('/homework/select-organization', function (Request $request) {
    $request->validate([
        'organization_id' => 'required|string',
        'state' => 'required|string',
        'client_id' => 'required|string',
    ]);

    $organizationId = $request->input('organization_id');

    $request->session()->put('selected_organization_id', $organizationId);

    $userId = auth()->id();
    cache()->put("org_selection:{$userId}", $organizationId, now()->addMinutes(5));

    return redirect('/oauth/authorize?' . http_build_query([
        'client_id' => $request->input('client_id'),
        'state' => $request->input('state'),
        'redirect_uri' => $request->input('redirect_uri'),
        'response_type' => 'code',
    ]));
})->middleware('web')->name('homework.select-organization');
