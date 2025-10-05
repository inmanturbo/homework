<?php

namespace Inmanturbo\Homework\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Inmanturbo\Homework\Contracts\AuthenticationResponseContract;
use Inmanturbo\Homework\Contracts\UserResponseContract;

class AuthenticationResponse implements AuthenticationResponseContract
{
    public function __construct(
        protected UserResponseContract $userResponse
    ) {}

    /**
     * Build the authentication response.
     */
    public function build(Authenticatable $user, array $tokenData): array
    {
        $userResponse = $this->userResponse->transform($user);

        $organizationId = $userResponse['organization_id'] ?? null;
        unset($userResponse['organization_id']);

        $response = [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'access_token_expires_at' => time() + ($tokenData['expires_in'] ?? 3600),
            'refresh_token_expires_at' => time() + (30 * 24 * 3600),
            'user' => $userResponse,
        ];

        if ($organizationId) {
            $response['organization_id'] = $organizationId;
        }

        return $response;
    }
}
