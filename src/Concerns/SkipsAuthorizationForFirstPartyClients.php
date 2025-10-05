<?php

namespace Inmanturbo\Homework\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;

trait SkipsAuthorizationForFirstPartyClients
{
    /**
     * Determine if the client should skip the authorization prompt.
     *
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $scopes
     * @return bool
     */
    public function skipsAuthorization(Authenticatable $user, array $scopes): bool
    {
        return empty($this->user_id);
    }
}
