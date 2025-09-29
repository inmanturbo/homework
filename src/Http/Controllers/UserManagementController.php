<?php

namespace Inmanturbo\Homework\Http\Controllers;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class UserManagementController extends Controller
{
    public function authenticate(Request $request): JsonResponse
    {
        $grantType = $request->input('grant_type');

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
        $clientId = $request->input('client_id');
        $redirectUri = $request->input('redirect_uri');

        if (! $redirectUri) {
            $client = \Laravel\Passport\Client::find($clientId);
            $redirectUri = $client ? $client->redirect : null;
        }

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
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $tokenData = json_decode($tokenResponse->getContent(), true);

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

        $finalResponse = [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'access_token_expires_at' => time() + ($tokenData['expires_in'] ?? 3600),
            'refresh_token_expires_at' => time() + (30 * 24 * 3600),
            'user' => $userResponse,
        ];

        return response()->json($finalResponse);
    }

    private function handleRefreshTokenFlow(Request $request): JsonResponse
    {
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

        return response()->json([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'access_token_expires_at' => time() + ($tokenData['expires_in'] ?? 3600),
            'refresh_token_expires_at' => time() + (30 * 24 * 3600),
            'user' => $userResponse,
        ]);
    }

    public function authenticateWithRefreshToken(Request $request): JsonResponse
    {
        return $this->handleRefreshTokenFlow($request);
    }

    public function jwks(string $clientId): JsonResponse
    {
        $key = config('app.key');

        $keyId = 'workos-local-key-1';

        return response()->json([
            'keys' => [
                [
                    'kty' => 'oct',
                    'use' => 'sig',
                    'alg' => 'HS256',
                    'kid' => $keyId,
                    'k' => $this->base64urlEncode($key),
                ],
            ],
        ]);
    }

    public function getUser(string $userId): JsonResponse
    {
        $userModel = config('auth.providers.users.model');
        $user = $userModel::find($userId);

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


    private function getUserFromToken(string $accessToken): Authenticatable
    {
        $request = \Illuminate\Http\Request::create('/api/user', 'GET');
        $request->headers->set('Authorization', 'Bearer ' . $accessToken);

        $guard = auth('api');
        $guard->setRequest($request);
        $user = $guard->user();

        if (! $user) {
            throw new \Exception('Invalid access token');
        }

        return $user;
    }

    protected function base64urlEncode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
