<?php

namespace Inmanturbo\Homework\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Inmanturbo\Homework\Contracts\UserResponseContract;

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

        return response()->json(
            app(UserResponseContract::class)->transform($user)
        );
    }
}
