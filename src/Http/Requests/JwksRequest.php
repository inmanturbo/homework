<?php

namespace Inmanturbo\Homework\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;

class JwksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function getJwks(): JsonResponse
    {
        $key = config('app.key');
        $keyId = 'workos-local-key-1';

        return response()->json([
            'keys' => [
                [
                    'kty' => 'oct',
                    'use' => 'sig',
                    'alg' => 'HS256',
                    'kid' => $keyId,
                    'k' => $this->base64urlEncode($key),
                ],
            ],
        ]);
    }

    private function base64urlEncode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
