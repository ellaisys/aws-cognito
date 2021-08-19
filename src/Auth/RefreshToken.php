<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Auth;

use Ellaisys\Cognito\AwsCognitoClient;

trait RefreshToken
{

    /**
     * @param string $refreshToken
     * @param string $username
     * @return mixed
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    protected function refreshToken(string $refreshToken, string $username)
    {
        return app()->make(AwsCognitoClient::class)->refreshToken($refreshToken, $username);
    } //Function ends

} //Trait ends
