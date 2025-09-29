<?php

namespace Inmanturbo\Homework\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Passport\Client;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;

class AutoApproveFirstPartyClients
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->method() !== 'GET' || !$request->is('oauth/authorize')) {
            return $next($request);
        }

        if (!$request->hasSession()) {
            return $next($request);
        }

        if (!$request->user()) {
            return $next($request);
        }

        $clientId = $request->input('client_id');
        if (!$clientId) {
            return $next($request);
        }

        $client = Client::find($clientId);
        if (!$client || !empty($client->user_id)) {
            return $next($request);
        }

        return $this->autoApprove($request);
    }

    private function autoApprove(Request $request)
    {
        $authToken = \Illuminate\Support\Str::random(40);

        $request->session()->put('authToken', $authToken);

        $authRequest = new AuthorizationRequest();
        $authRequest->setClient($this->getClientEntity($request->input('client_id')));
        $authRequest->setUser($this->getUserEntity());
        $authRequest->setRedirectUri($request->input('redirect_uri'));
        $authRequest->setState($request->input('state'));
        $authRequest->setGrantTypeId('authorization_code');

        $scopes = $request->input('scope') ? explode(' ', $request->input('scope')) : [];
        $authRequest->setScopes($this->getScopeEntities($scopes));

        if ($request->input('code_challenge')) {
            $authRequest->setCodeChallenge($request->input('code_challenge'));
            $authRequest->setCodeChallengeMethod($request->input('code_challenge_method', 'plain'));
        }

        $request->session()->put('authRequest', $authRequest);

        $approvalRequest = Request::create('/oauth/authorize', 'POST', [
            'state' => $request->input('state'),
            'client_id' => $request->input('client_id'),
            'auth_token' => $authToken,
            'approve' => '1',
            '_token' => $request->session()->token(),
        ]);

        $approvalRequest->headers = $request->headers;
        $approvalRequest->setSession($request->getSession());

        foreach ($request->cookies->all() as $name => $value) {
            $approvalRequest->cookies->set($name, $value);
        }

        return app()->handle($approvalRequest);
    }

    private function getClientEntity($clientId)
    {
        $client = Client::find($clientId);
        return new \Laravel\Passport\Bridge\Client($client->id, $client->name, $client->redirect);
    }

    private function getUserEntity()
    {
        $user = auth()->user();
        return new \Laravel\Passport\Bridge\User($user->getAuthIdentifier());
    }

    private function getScopeEntities(array $scopes)
    {
        $scopeEntities = [];
        foreach ($scopes as $scopeId) {
            $scopeEntities[] = new \Laravel\Passport\Bridge\Scope($scopeId);
        }
        return $scopeEntities;
    }
}