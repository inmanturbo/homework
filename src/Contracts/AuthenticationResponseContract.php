<?php

namespace Inmanturbo\Homework\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface AuthenticationResponseContract
{
    /**
     * Build the authentication response.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $tokenData  The token data from Passport (access_token, refresh_token, expires_in)
     * @return array  The complete authentication response
     */
    public function build(Authenticatable $user, array $tokenData): array;
}
