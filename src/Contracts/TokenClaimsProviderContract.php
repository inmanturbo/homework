<?php

namespace Inmanturbo\Homework\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface TokenClaimsProviderContract
{
    /**
     * Get the organization IDs for the user.
     *
     * Returns array of organization IDs the user belongs to.
     * If exactly one organization, org_id will be included in response.
     * If zero or multiple organizations, org_id will be omitted (stateless).
     *
     * @return array<string>
     */
    public function getOrganizations(Authenticatable $user): array;

    /**
     * Get the permissions for the user.
     *
     * Returns array of permission strings (e.g., ['read:users', 'write:posts']).
     * Return null to omit permissions from response.
     *
     * @return array<string>|null
     */
    public function getPermissions(Authenticatable $user): ?array;

    /**
     * Get the roles for the user.
     *
     * Returns array of role strings (e.g., ['admin', 'member']).
     * Return null to omit roles from response.
     *
     * @return array<string>|null
     */
    public function getRoles(Authenticatable $user): ?array;

    /**
     * Get custom metadata for the user.
     *
     * Returns associative array of custom claims/metadata.
     * These will be merged into the user object.
     * Return null or empty array to omit custom metadata.
     */
    public function getMetadata(Authenticatable $user): ?array;
}
