<?php

namespace Inmanturbo\Homework\Services;

use Illuminate\Support\Arr;
use Laravel\Passport\ClientRepository;

class ClientService
{
    /**
     * Create a first-party WorkOS-compatible OAuth client.
     *
     * @param  string|array  $redirectUris  Single URI or array of redirect URIs
     * @param  string  $name  Client name
     * @return array Client configuration details
     */
    public static function createFirstPartyWorkOsClient(
        string|array $redirectUris,
        string $name = 'WorkOS Client'
    ): array {
        $repository = new ClientRepository;

        $uris = Arr::wrap($redirectUris);

        // Create an authorization code grant client (first-party)
        $client = $repository->createAuthorizationCodeGrantClient(
            name: $name,
            redirectUris: $uris,
        );

        $baseUrl = config('app.url');
        if (! str_ends_with($baseUrl, '/')) {
            $baseUrl .= '/';
        }

        return [
            'client_id' => $client->id,
            'client_secret' => $client->plainSecret,
            'redirect_uris' => $uris,
            'base_url' => $baseUrl,
            'env_config' => [
                'WORKOS_CLIENT_ID' => $client->id,
                'WORKOS_API_KEY' => $client->plainSecret,
                'WORKOS_REDIRECT_URL' => $uris[0],
                'WORKOS_BASE_URL' => $baseUrl,
            ],
        ];
    }
}
