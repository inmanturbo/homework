<?php

namespace Inmanturbo\Homework\Http\Requests;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;

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
            \Log::error('Passport authorization code exchange failed', [
                'status' => $tokenResponse->getStatusCode(),
                'response' => $tokenResponse->getContent(),
                'client_id' => $clientId,
                'has_code' => !empty($this->input('code')),
                'redirect_uri' => $redirectUri,
            ]);

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
            \Log::error('Passport refresh token exchange failed', [
                'status' => $tokenResponse->getStatusCode(),
                'response' => $tokenResponse->getContent(),
                'client_id' => $this->input('client_id'),
                'has_refresh_token' => !empty($this->input('refresh_token')),
            ]);

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
