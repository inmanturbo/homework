<?php

namespace Inmanturbo\Homework\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inmanturbo\Homework\Contracts\OrganizationProviderContract;
use Inmanturbo\Homework\Homework;

class RequireOrganizationSelection
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->is('oauth/authorize')) {
            return $next($request);
        }

        if (! app()->bound(OrganizationProviderContract::class)) {
            return $next($request);
        }

        $user = Auth::user();
        if (! $user) {
            return $next($request);
        }

        $provider = app(OrganizationProviderContract::class);
        $organizations = collect($provider->getOrganizationsForUser($user));

        if ($organizations->count() <= 1) {
            return $next($request);
        }

        if ($request->session()->has('selected_organization_id')) {
            return $next($request);
        }

        return Homework::renderOrganizationSelectionView([
            'organizations' => $organizations,
            'user' => $user,
            'state' => $request->input('state'),
            'clientId' => $request->input('client_id'),
            'redirectUri' => $request->input('redirect_uri'),
            'responseType' => $request->input('response_type', 'code'),
        ]);
    }
}
