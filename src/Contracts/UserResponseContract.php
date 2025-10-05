<?php

namespace Inmanturbo\Homework\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface UserResponseContract
{
    /**
     * Transform a user model into a WorkOS-compatible response.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return array
     */
    public function transform(Authenticatable $user): array;
}
