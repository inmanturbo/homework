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
        // Only apply to OAuth authorization requests
        if (! $request->is('oauth/authorize')) {
            return $next($request);
        }

        // Skip if organization provider is not bound
        if (! app()->bound(OrganizationProviderContract::class)) {
            return $next($request);
        }

        $user = Auth::user();
        if (! $user) {
            return $next($request);
        }

        // Get organizations for user
        $provider = app(OrganizationProviderContract::class);
        $organizations = collect($provider->getOrganizationsForUser($user));

        // If no organizations or only one, proceed normally
        if ($organizations->count() <= 1) {
            return $next($request);
        }

        // Check if organization already selected in session
        if ($request->session()->has('selected_organization_id')) {
            return $next($request);
        }

        // Show organization selection view (headless or default)
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
