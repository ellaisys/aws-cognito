<?php

namespace Ellaisys\Cognito\Auth;

trait LocalUser
{

    /**
     * Create a local user if one does not exist.
     *
     * @param array $credentials
     * @return mixed
     */
    protected function createLocalUser($credentials)
    {
        $userModel = config('cognito.sso_user_model');
        $user = $userModel::create($credentials);

        return $user;
    } //Function ends
}