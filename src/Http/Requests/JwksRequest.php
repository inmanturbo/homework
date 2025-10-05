<?php

namespace Inmanturbo\Homework\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Laravel\Passport\Passport;

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
        $publicKeyPath = Passport::keyPath('oauth-public.key');
        $publicKeyContent = file_get_contents($publicKeyPath);

        $publicKey = openssl_pkey_get_public($publicKeyContent);
        $keyDetails = openssl_pkey_get_details($publicKey);

        $n = $this->base64urlEncode($keyDetails['rsa']['n']);
        $e = $this->base64urlEncode($keyDetails['rsa']['e']);

        return response()->json([
            'keys' => [
                [
                    'kty' => 'RSA',
                    'use' => 'sig',
                    'alg' => 'RS256',
                    'n' => $n,
                    'e' => $e,
                ],
            ],
        ]);
    }

    private function base64urlEncode($data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
