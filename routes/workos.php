<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inmanturbo\Homework\Http\Controllers\UserManagementController;

Route::prefix('user_management')->group(function () {

    Route::get('/authorize', function (Request $request) {
        return redirect('/oauth/authorize?' . $request->getQueryString());
    });

    Route::post('/authenticate', [UserManagementController::class, 'authenticate']);
    Route::post('/authenticate_with_refresh_token', [UserManagementController::class, 'authenticateWithRefreshToken']);
    Route::get('/users/{userId}', [UserManagementController::class, 'getUser'])->middleware('auth:api');
});

Route::prefix('sso')->group(function () {
    Route::get('/jwks/{clientId}', [UserManagementController::class, 'jwks']);
});
