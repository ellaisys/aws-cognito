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

use Illuminate\Http\Request;

use Ellaisys\Cognito\AwsCognitoClient;

use Exception;
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;

trait RegistersUsers
{

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     * @throws InvalidUserFieldException
     */
    public function createCognitoUser(Request $request)
    {
        //Initialize Cognito Attribute array
        $attributes = [];

        //Get the registeration fields
        $userFields = config('cognito.sso_user_fields');

        //Iterate the fields
        foreach ($userFields as $userField) {
            if ($request->filled($userField)) {
                $attributes[$userField] = $request->get($userField);
            } else {
                throw new InvalidUserFieldException("The configured user field {$userField} is not provided in the request.");
            } //End if
        } //Loop ends

        //Register the user in Cognito
        $userKey = $request->has('username')?'username':'email';
        return app()->make(AwsCognitoClient::class)->register($request->input($userKey), $request->password, $request->email, $attributes);
    } //Function ends

} //Trait ends