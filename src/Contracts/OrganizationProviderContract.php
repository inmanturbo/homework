<?php

namespace Inmanturbo\Homework\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface OrganizationProviderContract
{
    /**
     * Get organizations for the given user.
     *
     * @return array Array of ['id' => string, 'name' => string]
     */
    public function getOrganizationsForUser(Authenticatable $user): array;
}
