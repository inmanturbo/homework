<?php

namespace Inmanturbo\Homework\Http\Controllers;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class UserManagementController extends Controller
{
    public function authenticate(Request $request): JsonResponse
    {
        $grantType = $request->input('grant_type');

        // Handle different grant types
        switch ($grantType) {
            case 'authorization_code':
                return $this->handleAuthorizationCodeFlow($request);
            case 'refresh_token':
                return $this->handleRefreshTokenFlow($request);
            default:
                return response()->json(['error' => 'unsupported_grant_type'], 400);
        }
    }

    private function handleAuthorizationCodeFlow(Request $request): JsonResponse
    {
        // Debug: log all incoming parameters
        \Log::info('Token exchange request received', [
            'all_parameters' => $request->all(),
            'client_id' => $request->input('client_id'),
            'redirect_uri' => $request->input('redirect_uri'),
            'code' => substr($request->input('code'), 0, 50) . '...', // Truncate for logging
        ]);

        // Get the redirect_uri from request or use the client's configured redirect_uri
        $clientId = $request->input('client_id');
        $redirectUri = $request->input('redirect_uri');

        // If no redirect_uri provided, get it from the client configuration
        if (! $redirectUri) {
            $client = \Laravel\Passport\Client::find($clientId);
            $redirectUri = $client ? $client->redirect : null;
            \Log::info('Using client configured redirect_uri', ['redirect_uri' => $redirectUri]);
        }

        // Use Passport's token endpoint to exchange authorization code for tokens
        $tokenRequest = \Illuminate\Http\Request::create('/oauth/token', 'POST', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $request->input('client_secret'),
            'code' => $request->input('code'),
            'redirect_uri' => $redirectUri,
            'code_verifier' => $request->input('code_verifier'),
        ]);

        $tokenResponse = app()->handle($tokenRequest);

        if ($tokenResponse->getStatusCode() !== 200) {
            // Debug: log the actual error from Passport
            \Log::error('Passport token exchange failed', [
                'status' => $tokenResponse->getStatusCode(),
                'response' => $tokenResponse->getContent(),
                'request_data' => $tokenRequest->all(),
            ]);

            return response()->json([
                'error' => 'invalid_grant',
                'debug' => json_decode($tokenResponse->getContent(), true),
            ], 400);
        }

        $tokenData = json_decode($tokenResponse->getContent(), true);

        // Get user information from the access token
        $user = $this->getUserFromToken($tokenData['access_token']);

        $userResponse = [
            'object' => 'user',
            'id' => (string) $user->id,
            'email' => $user->email,
            'first_name' => explode(' ', $user->name, 2)[0] ?? '',
            'last_name' => isset(explode(' ', $user->name, 2)[1]) ? explode(' ', $user->name, 2)[1] : '',
            'email_verified' => ! is_null($user->email_verified_at),
            'profile_picture_url' => null,
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ];

        Log::info('User response being sent', [
            'user_name_from_db' => $user->name,
            'first_name' => $userResponse['first_name'],
            'last_name' => $userResponse['last_name'],
            'full_response' => $userResponse,
        ]);

        // Final response that will be sent to client
        $finalResponse = [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'access_token_expires_at' => time() + ($tokenData['expires_in'] ?? 3600),
            'refresh_token_expires_at' => time() + (30 * 24 * 3600), // 30 days
            'user' => $userResponse,
        ];

        Log::info('Final JSON response', ['response' => $finalResponse]);

        // Return WorkOS-compatible response format
        return response()->json($finalResponse);
    }

    private function handleRefreshTokenFlow(Request $request): JsonResponse
    {
        // Use Passport's token endpoint to refresh the token
        $tokenRequest = \Illuminate\Http\Request::create('/oauth/token', 'POST', [
            'grant_type' => 'refresh_token',
            'client_id' => $request->input('client_id'),
            'client_secret' => $request->input('client_secret'),
            'refresh_token' => $request->input('refresh_token'),
        ]);

        $tokenResponse = app()->handle($tokenRequest);

        if ($tokenResponse->getStatusCode() !== 200) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $tokenData = json_decode($tokenResponse->getContent(), true);

        // Get user information from the new access token
        $user = $this->getUserFromToken($tokenData['access_token']);

        $userResponse = [
            'object' => 'user',
            'id' => (string) $user->id,
            'email' => $user->email,
            'first_name' => explode(' ', $user->name, 2)[0] ?? '',
            'last_name' => isset(explode(' ', $user->name, 2)[1]) ? explode(' ', $user->name, 2)[1] : '',
            'email_verified' => ! is_null($user->email_verified_at),
            'profile_picture_url' => null,
            'created_at' => $user->created_at->toISOString(),
            'updated_at' => $user->updated_at->toISOString(),
        ];

        Log::info('Refresh token - User response being sent', [
            'user_name_from_db' => $user->name,
            'first_name' => $userResponse['first_name'],
            'last_name' => $userResponse['last_name'],
        ]);

        return response()->json([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'access_token_expires_at' => time() + ($tokenData['expires_in'] ?? 3600),
            'refresh_token_expires_at' => time() + (30 * 24 * 3600), // 30 days
            'user' => $userResponse,
        ]);
    }

    public function authenticateWithRefreshToken(Request $request): JsonResponse
    {
        // Use Passport's token endpoint to refresh the token
        $tokenRequest = \Illuminate\Http\Request::create('/oauth/token', 'POST', [
            'grant_type' => 'refresh_token',
            'client_id' => $request->input('client_id'),
            'client_secret' => $request->input('client_secret'),
            'refresh_token' => $request->input('refresh_token'),
        ]);

        $tokenResponse = app()->handle($tokenRequest);

        if ($tokenResponse->getStatusCode() !== 200) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $tokenData = json_decode($tokenResponse->getContent(), true);

        return response()->json([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'access_token_expires_at' => time() + ($tokenData['expires_in'] ?? 3600),
            'refresh_token_expires_at' => time() + (30 * 24 * 3600), // 30 days
        ]);
    }

    public function jwks(string $clientId): JsonResponse
    {
        // Return JWKS for token verification
        // For development, we'll use the same signing key as our JWT tokens
        // In production, you should use proper RSA keys

        $key = config('app.key');

        // Generate a simple key ID for the JWKS
        $keyId = 'workos-local-key-1';

        return response()->json([
            'keys' => [
                [
                    'kty' => 'oct',
                    'use' => 'sig',
                    'alg' => 'HS256',
                    'kid' => $keyId,
                    'k' => base64url_encode($key),
                ],
            ],
        ]);
    }

    public function getUser(string $userId): JsonResponse
    {
        $user = \App\Models\User::find($userId);

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'id' => (string) $user->id,
            'email' => $user->email,
            'firstName' => explode(' ', $user->name)[0] ?? '',
            'lastName' => explode(' ', $user->name, 2)[1] ?? '',
            'emailVerified' => ! is_null($user->email_verified_at),
            'createdAt' => $user->created_at->toISOString(),
            'updatedAt' => $user->updated_at->toISOString(),
        ]);
    }

    private function generateAccessToken(int $userId): string
    {
        $user = \App\Models\User::find($userId);

        $timestamp = time();
        $payload = [
            'iss' => config('app.url'),
            'sub' => (string) $userId,
            'aud' => config('services.workos.client_id'),
            'exp' => $timestamp + 3600,
            'iat' => $timestamp,
            'jti' => uniqid(), // Add unique identifier to ensure tokens are different
            'email' => $user->email,
            'name' => $user->name,
        ];

        // Use app key for signing
        $key = config('app.key');
        $keyId = 'workos-local-key-1';

        return JWT::encode($payload, $key, 'HS256', $keyId);
    }

    private function getUserFromToken(string $accessToken): \App\Models\User
    {
        // Create a request with the access token and use Passport's auth guard
        $request = \Illuminate\Http\Request::create('/api/user', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $accessToken);

        // Use Passport's API guard to get the user
        $guard = auth('api');
        $guard->setRequest($request);
        $user = $guard->user();

        if (! $user) {
            throw new \Exception('Invalid access token');
        }

        Log::info('User retrieved from token', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name ?? 'NULL',
            'user_attributes' => $user->getAttributes(),
        ]);

        return $user;
    }
}

// Helper function for base64url encoding
if (! function_exists('base64url_encode')) {
    function base64url_encode($data)
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
