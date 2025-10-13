<?php

use Illuminate\Contracts\Auth\Authenticatable;
use Inmanturbo\Homework\Contracts\TokenClaimsProviderContract;
use Inmanturbo\Homework\Homework;
use Inmanturbo\Homework\Support\AuthenticationResponse;
use Inmanturbo\Homework\Support\UserResponse;

beforeEach(function () {
    $this->userResponse = new UserResponse();
    $this->authResponse = new AuthenticationResponse($this->userResponse);
});

test('authentication response includes org_id when user has exactly one organization', function () {
    $claimsProvider = new class() implements TokenClaimsProviderContract
    {
        public function getOrganizations(Authenticatable $user): array
        {
            return ['org_123'];
        }

        public function getPermissions(Authenticatable $user): ?array
        {
            return null;
        }

        public function getRoles(Authenticatable $user): ?array
        {
            return null;
        }

        public function getMetadata(Authenticatable $user): ?array
        {
            return null;
        }
    };

    Homework::useTokenClaimsProvider($claimsProvider::class);

    $user = createTestUser();
    $tokenData = ['access_token' => 'token', 'refresh_token' => 'refresh', 'expires_in' => 3600];

    $response = $this->authResponse->build($user, $tokenData);

    expect($response)->toHaveKey('org_id')
        ->and($response['org_id'])->toBe('org_123');
});

test('authentication response omits org_id when user has multiple organizations', function () {
    $claimsProvider = new class() implements TokenClaimsProviderContract
    {
        public function getOrganizations(Authenticatable $user): array
        {
            return ['org_123', 'org_456'];
        }

        public function getPermissions(Authenticatable $user): ?array
        {
            return null;
        }

        public function getRoles(Authenticatable $user): ?array
        {
            return null;
        }

        public function getMetadata(Authenticatable $user): ?array
        {
            return null;
        }
    };

    Homework::useTokenClaimsProvider($claimsProvider::class);

    $user = createTestUser();
    $tokenData = ['access_token' => 'token', 'refresh_token' => 'refresh', 'expires_in' => 3600];

    $response = $this->authResponse->build($user, $tokenData);

    expect($response)->not->toHaveKey('org_id');
});

test('authentication response omits org_id when user has zero organizations', function () {
    $claimsProvider = new class() implements TokenClaimsProviderContract
    {
        public function getOrganizations(Authenticatable $user): array
        {
            return [];
        }

        public function getPermissions(Authenticatable $user): ?array
        {
            return null;
        }

        public function getRoles(Authenticatable $user): ?array
        {
            return null;
        }

        public function getMetadata(Authenticatable $user): ?array
        {
            return null;
        }
    };

    Homework::useTokenClaimsProvider($claimsProvider::class);

    $user = createTestUser();
    $tokenData = ['access_token' => 'token', 'refresh_token' => 'refresh', 'expires_in' => 3600];

    $response = $this->authResponse->build($user, $tokenData);

    expect($response)->not->toHaveKey('org_id');
});

test('authentication response includes permissions when provided', function () {
    $claimsProvider = new class() implements TokenClaimsProviderContract
    {
        public function getOrganizations(Authenticatable $user): array
        {
            return [];
        }

        public function getPermissions(Authenticatable $user): ?array
        {
            return ['read:users', 'write:posts'];
        }

        public function getRoles(Authenticatable $user): ?array
        {
            return null;
        }

        public function getMetadata(Authenticatable $user): ?array
        {
            return null;
        }
    };

    Homework::useTokenClaimsProvider($claimsProvider::class);

    $user = createTestUser();
    $tokenData = ['access_token' => 'token', 'refresh_token' => 'refresh', 'expires_in' => 3600];

    $response = $this->authResponse->build($user, $tokenData);

    expect($response['user'])->toHaveKey('permissions')
        ->and($response['user']['permissions'])->toBe(['read:users', 'write:posts']);
});

test('authentication response includes roles when provided', function () {
    $claimsProvider = new class() implements TokenClaimsProviderContract
    {
        public function getOrganizations(Authenticatable $user): array
        {
            return [];
        }

        public function getPermissions(Authenticatable $user): ?array
        {
            return null;
        }

        public function getRoles(Authenticatable $user): ?array
        {
            return ['admin', 'member'];
        }

        public function getMetadata(Authenticatable $user): ?array
        {
            return null;
        }
    };

    Homework::useTokenClaimsProvider($claimsProvider::class);

    $user = createTestUser();
    $tokenData = ['access_token' => 'token', 'refresh_token' => 'refresh', 'expires_in' => 3600];

    $response = $this->authResponse->build($user, $tokenData);

    expect($response['user'])->toHaveKey('roles')
        ->and($response['user']['roles'])->toBe(['admin', 'member']);
});

test('authentication response includes metadata when provided', function () {
    $claimsProvider = new class() implements TokenClaimsProviderContract
    {
        public function getOrganizations(Authenticatable $user): array
        {
            return [];
        }

        public function getPermissions(Authenticatable $user): ?array
        {
            return null;
        }

        public function getRoles(Authenticatable $user): ?array
        {
            return null;
        }

        public function getMetadata(Authenticatable $user): ?array
        {
            return [
                'department' => 'Engineering',
                'employee_id' => 'EMP001',
            ];
        }
    };

    Homework::useTokenClaimsProvider($claimsProvider::class);

    $user = createTestUser();
    $tokenData = ['access_token' => 'token', 'refresh_token' => 'refresh', 'expires_in' => 3600];

    $response = $this->authResponse->build($user, $tokenData);

    expect($response['user'])->toHaveKey('department')
        ->and($response['user']['department'])->toBe('Engineering')
        ->and($response['user'])->toHaveKey('employee_id')
        ->and($response['user']['employee_id'])->toBe('EMP001');
});

test('authentication response works without token claims provider', function () {
    $user = createTestUser();
    $tokenData = ['access_token' => 'token', 'refresh_token' => 'refresh', 'expires_in' => 3600];

    $response = $this->authResponse->build($user, $tokenData);

    expect($response)->toHaveKey('access_token')
        ->and($response)->toHaveKey('user')
        ->and($response)->not->toHaveKey('org_id');
});

function createTestUser(): Authenticatable
{
    return new class() implements Authenticatable
    {
        public function getAuthIdentifierName()
        {
            return 'id';
        }

        public function getAuthIdentifier()
        {
            return '1';
        }

        public function getAuthPassword()
        {
            return '';
        }

        public function getAuthPasswordName()
        {
            return 'password';
        }

        public function getRememberToken()
        {
            return '';
        }

        public function setRememberToken($value) {}

        public function getRememberTokenName()
        {
            return '';
        }

        public function __get($key)
        {
            $data = [
                'id' => '1',
                'email' => 'test@example.com',
                'name' => 'Test User',
            ];

            return $data[$key] ?? null;
        }
    };
}
