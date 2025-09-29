<?php

namespace Inmanturbo\Homework\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;

class GetUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function fetchUser(): JsonResponse
    {
        $userId = $this->route('userId');

        $userModel = config('auth.providers.users.model');
        $user = $userModel::find($userId);

        if (! $user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        return response()->json([
            'id' => (string) $user->id,
            'email' => $user->email,
            'firstName' => explode(' ', $user->name)[0] ?? '',
            'lastName' => explode(' ', $user->name, 2)[1] ?? '',
            'emailVerified' => ! is_null($user->email_verified_at),
            'createdAt' => $user->created_at->toISOString(),
            'updatedAt' => $user->updated_at->toISOString(),
        ]);
    }
}
