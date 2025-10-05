<?php

namespace Inmanturbo\Homework\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;

trait SkipsAuthorizationForFirstPartyClients
{
    /**
     * Determine if the client should skip the authorization prompt.
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return empty($this->user_id);
    }
}
