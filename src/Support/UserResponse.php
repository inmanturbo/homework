<?php

namespace Inmanturbo\Homework\Support;

use Illuminate\Contracts\Auth\Authenticatable;
use Inmanturbo\Homework\Contracts\UserResponseContract;
use Laravel\WorkOS\User as WorkOsUser;

class UserResponse implements UserResponseContract
{
    /**
     * Transform a user model into a WorkOS-compatible response.
     */
    public function transform(Authenticatable $user): array
    {
        if (method_exists($user, 'workosUser')) {
            $workosUser = $user->workosUser();
        } else {
            $workosUser = $this->createWorkOsUser($user);
        }

        return $this->formatResponse($workosUser, $user);
    }

    /**
     * Create a WorkOS User object from an Authenticatable user.
     */
    protected function createWorkOsUser(Authenticatable $user): WorkOsUser
    {
        $nameParts = explode(' ', $user->name ?? '', 2);

        // Check for selected organization in multiple locations:
        // 1. Session (during authorization flow)
        // 2. Cache (persisted from organization selection for token exchange)
        // 3. User model attribute (if stored on user)
        $organizationId = session('selected_organization_id')
            ?? cache()->get("org_selection:{$user->id}")
            ?? $user->organization_id
            ?? null;

        // Clear the cache after retrieving to prevent reuse
        if ($organizationId && cache()->has("org_selection:{$user->id}")) {
            cache()->forget("org_selection:{$user->id}");
        }

        return new WorkOsUser(
            id: (string) $user->id,
            organizationId: $organizationId,
            firstName: $nameParts[0] ?? null,
            lastName: $nameParts[1] ?? null,
            email: $user->email,
            avatar: $this->getProfilePictureUrl($user),
        );
    }

    /**
     * Format the WorkOS User into the response array.
     */
    protected function formatResponse(WorkOsUser $workosUser, Authenticatable $user): array
    {
        $response = [
            'object' => 'user',
            'id' => $workosUser->id,
            'email' => $workosUser->email,
            'first_name' => $workosUser->firstName,
            'last_name' => $workosUser->lastName,
            'email_verified' => ! is_null($user->email_verified_at ?? null),
            'profile_picture_url' => $workosUser->avatar,
            'created_at' => $user->created_at?->toISOString(),
            'updated_at' => $user->updated_at?->toISOString(),
        ];

        if ($workosUser->organizationId !== null) {
            $response['organization_id'] = $workosUser->organizationId;
        }

        return $response;
    }

    /**
     * Get the profile picture URL for the user.
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
