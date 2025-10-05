<?php

namespace Inmanturbo\Homework;

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
}
