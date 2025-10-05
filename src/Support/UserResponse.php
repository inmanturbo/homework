<?php

namespace Inmanturbo\Homework\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Inmanturbo\Homework\Contracts\UserResponseContract;

class UserResponse implements UserResponseContract
{
    /**
     * Transform a user model into a WorkOS-compatible response.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return array
     */
    public function transform(Authenticatable $user): array
    {
        $nameParts = explode(' ', $user->name ?? '', 2);

        return [
            'object' => 'user',
            'id' => (string) $user->id,
            'email' => $user->email,
            'first_name' => $nameParts[0] ?? '',
            'last_name' => $nameParts[1] ?? '',
            'email_verified' => ! is_null($user->email_verified_at ?? null),
            'profile_picture_url' => $this->getProfilePictureUrl($user),
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];
    }

    /**
     * Get the profile picture URL for the user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @return string|null
     */
    protected function getProfilePictureUrl(Authenticatable $user): ?string
    {
        // Check common avatar attributes
        if (isset($user->avatar_url)) {
            return $user->avatar_url;
        }

        if (isset($user->profile_picture_url)) {
            return $user->profile_picture_url;
        }

        if (isset($user->avatar)) {
            return $user->avatar;
        }

        return null;
    }
}
