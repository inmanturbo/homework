<?php

namespace Inmanturbo\Homework\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Inmanturbo\Homework\Contracts\AuthenticationResponseContract;
use Inmanturbo\Homework\Contracts\TokenClaimsProviderContract;
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

        $orgId = null;
        $claimsProvider = $this->resolveTokenClaimsProvider();

        if ($claimsProvider) {
            $orgId = $this->resolveOrgId($user, $claimsProvider);
            $userResponse = $this->applyTokenClaims($user, $userResponse, $claimsProvider);
        }

        $response = [
            'access_token' => $tokenData['access_token'],
            'refresh_token' => $tokenData['refresh_token'],
            'access_token_expires_at' => time() + ($tokenData['expires_in'] ?? 3600),
            'refresh_token_expires_at' => time() + (30 * 24 * 3600),
            'user' => $userResponse,
        ];

        if ($orgId) {
            $response['org_id'] = $orgId;
        } elseif ($organizationId) {
            $response['organization_id'] = $organizationId;
        }

        return $response;
    }

    protected function resolveTokenClaimsProvider(): ?TokenClaimsProviderContract
    {
        if (app()->bound(TokenClaimsProviderContract::class)) {
            return app(TokenClaimsProviderContract::class);
        }

        return null;
    }

    protected function resolveOrgId(Authenticatable $user, TokenClaimsProviderContract $provider): ?string
    {
        $organizations = $provider->getOrganizations($user);

        if (count($organizations) === 1) {
            return $organizations[0];
        }

        return null;
    }

    protected function applyTokenClaims(Authenticatable $user, array $userResponse, TokenClaimsProviderContract $provider): array
    {
        $permissions = $provider->getPermissions($user);
        if ($permissions !== null) {
            $userResponse['permissions'] = $permissions;
        }

        $roles = $provider->getRoles($user);
        if ($roles !== null) {
            $userResponse['roles'] = $roles;
        }

        $metadata = $provider->getMetadata($user);
        if ($metadata !== null && ! empty($metadata)) {
            $userResponse = array_merge($userResponse, $metadata);
        }

        return $userResponse;
    }
}
