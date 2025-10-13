<?php

namespace Inmanturbo\Homework;

use Inmanturbo\Homework\Contracts\AuthenticationResponseContract;
use Inmanturbo\Homework\Contracts\OrganizationProviderContract;
use Inmanturbo\Homework\Contracts\UserResponseContract;

class Homework
{
    /**
     * The callback that should be used to render the organization selection view.
     *
     * @var callable|null
     */
    public static $organizationSelectionViewCallback;

    /**
     * Set a callback that should be used to render the organization selection view.
     *
     * @return void
     */
    public static function organizationSelectionView(callable $callback)
    {
        static::$organizationSelectionViewCallback = $callback;
    }

    /**
     * Determine if the organization selection view callback has been set.
     *
     * @return bool
     */
    public static function hasOrganizationSelectionView()
    {
        return static::$organizationSelectionViewCallback !== null;
    }

    /**
     * Render the organization selection view.
     *
     * @return \Illuminate\Http\Response
     */
    public static function renderOrganizationSelectionView(array $data)
    {
        if (static::hasOrganizationSelectionView()) {
            return call_user_func(static::$organizationSelectionViewCallback, $data);
        }

        return response()->view('homework::auth.select-organization', $data);
    }

    /**
     * Set the organization provider implementation.
     *
     * @param  string  $provider  The organization provider class name
     * @return void
     */
    public static function useOrganizationProvider(string $provider)
    {
        app()->bind(OrganizationProviderContract::class, $provider);
    }

    /**
     * Set the user response implementation.
     *
     * @param  string  $response  The user response class name
     * @return void
     */
    public static function useUserResponse(string $response)
    {
        app()->bind(UserResponseContract::class, $response);
    }

    /**
     * Set the authentication response implementation.
     *
     * @param  string  $response  The authentication response class name
     * @return void
     */
    public static function useAuthenticationResponse(string $response)
    {
        app()->bind(AuthenticationResponseContract::class, $response);
    }
}
