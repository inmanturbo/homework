<?php

namespace Inmanturbo\Homework\Http\Requests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Inmanturbo\Homework\Contracts\UserResponseContract;

class AuthenticateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'grant_type' => 'required|in:authorization_code,refresh_token',
            'client_id' => 'required|string',
            'client_secret' => 'nullable|string',
            'code' => 'required_if:grant_type,authorization_code|string',
            'redirect_uri' => 'nullable|string',
            'code_verifier' => 'nullable|string',
            'refresh_token' => 'required_if:grant_type,refresh_token|string',
        ];
    }

    public function authenticate(): JsonResponse
    {
        $grantType = $this->input('grant_type');

        return match ($grantType) {
            'authorization_code' => $this->handleAuthorizationCodeFlow(),
            'refresh_token' => $this->handleRefreshTokenFlow(),
            default => response()->json(['error' => 'unsupported_grant_type'], 400),
        };
    }

    private function handleAuthorizationCodeFlow(): JsonResponse
    {
        $clientId = $this->input('client_id');
        $redirectUri = $this->input('redirect_uri');

        if (! $redirectUri) {
            $client = \Laravel\Passport\Client::find($clientId);
            if ($client && isset($client->redirect_uris[0])) {
                $redirectUri = $client->redirect_uris[0];
            }
        }

        $tokenRequest = \Illuminate\Http\Request::create('/oauth/token', 'POST', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'client_secret' => $this->input('client_secret'),
            'code' => $this->input('code'),
            'redirect_uri' => $redirectUri,
            'code_verifier' => $this->input('code_verifier'),
        ]);

        $tokenResponse = app()->handle($tokenRequest);

        if ($tokenResponse->getStatusCode() !== 200) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $tokenData = json_decode($tokenResponse->getContent(), true);

        $user = $this->getUserFromToken($tokenData['access_token']);

        $userResponse = app(UserResponseContract::class)->transform($user);

        $finalResponse = [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'access_token_expires_at' => time() + ($tokenData['expires_in'] ?? 3600),
            'refresh_token_expires_at' => time() + (30 * 24 * 3600),
            'user' => $userResponse,
        ];

        return response()->json($finalResponse);
    }

    private function handleRefreshTokenFlow(): JsonResponse
    {
        $tokenRequest = \Illuminate\Http\Request::create('/oauth/token', 'POST', [
            'grant_type' => 'refresh_token',
            'client_id' => $this->input('client_id'),
            'client_secret' => $this->input('client_secret'),
            'refresh_token' => $this->input('refresh_token'),
        ]);

        $tokenResponse = app()->handle($tokenRequest);

        if ($tokenResponse->getStatusCode() !== 200) {
            return response()->json(['error' => 'invalid_grant'], 400);
        }

        $tokenData = json_decode($tokenResponse->getContent(), true);

        $user = $this->getUserFromToken($tokenData['access_token']);

        $userResponse = app(UserResponseContract::class)->transform($user);

        return response()->json([
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'access_token_expires_at' => time() + ($tokenData['expires_in'] ?? 3600),
            'refresh_token_expires_at' => time() + (30 * 24 * 3600),
            'user' => $userResponse,
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
}
