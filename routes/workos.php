<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inmanturbo\Homework\Http\Controllers\UserManagementController;

// WorkOS UserManagement API routes - using Laravel Passport OAuth flow
Route::prefix('user_management')->group(function () {
    // Route WorkOS authorize to Passport's authorize endpoint
    Route::get('/authorize', function (Request $request) {
        // Redirect to Passport's OAuth authorize route with all parameters
        return redirect('/oauth/authorize?' . $request->getQueryString());
    });

    Route::post('/authenticate', [UserManagementController::class, 'authenticate']);
    Route::post('/authenticate_with_refresh_token', [UserManagementController::class, 'authenticateWithRefreshToken']);
    Route::get('/users/{userId}', [UserManagementController::class, 'getUser'])->middleware('auth:api');
});

// WorkOS SSO routes (for JWKS)
Route::prefix('sso')->group(function () {
    Route::get('/jwks/{clientId}', [UserManagementController::class, 'jwks']);
});
