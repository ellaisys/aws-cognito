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

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request, array $clientMetadata=null)
    {
        $cognitoRegistered=false;
        $user = [];

        //Validate request
        $this->validator($request->all())->validate();

        //Create data to save
        $data = $request->all();

        //Create credentials object
        $collection = collect($data);

        //Register User in Cognito
        $cognitoRegistered=$this->createCognitoUser($collection, $clientMetadata, config('cognito.default_user_group', null));
        if ($cognitoRegistered==true) {
            //Remove the password
            if(!empty($data['password'])) {
                unset($data['password']);
            } //End if

            //Create user in local store
            $user = $this->create($data);
        } //End if

        // Return with user data
        return $request->wantsJson()
            ? new JsonResponse($user, 201)
            : redirect($this->redirectPath());
    } //Function ends


    /**
     * Adds the newly created user to the default group (if one exists) in the config file.
     *
     * @param $username
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function setDefaultGroup($username)
    {
        if (!empty(config('cognito.default_user_group', null))) {
            return app()->make(AwsCognitoClient::class)->adminAddUserToGroup(
                $username, config('cognito.default_user_group', null)
            );
        } //End if
        return [];
    } //Function ends


    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Support\Collection  $request
     * @return \Illuminate\Http\Response
     * @throws InvalidUserFieldException
     */
    public function createCognitoUser(Collection $request, array $clientMetadata=null, string $groupname=null)
    {
        //Initialize Cognito Attribute array
        $attributes = [];

        //Get the configuration for new user invitation message action.
        $messageAction = config('cognito.new_user_message_action', null);

        //Get the configuration for the forced verification of new user
        $isUserEmailForcedVerified = config('cognito.force_new_user_email_verified', false);

        //Get the registeration fields
        $userFields = config('cognito.cognito_user_fields');

        //Iterate the fields
        foreach ($userFields as $key => $userField) {
            if ($request->has($userField)) {
                $attributes[$key] = $request->get($userField);
            } else {
                Log::error('RegistersUsers:createCognitoUser:InvalidUserFieldException');
                Log::error("The configured user field {$userField} is not provided in the request.");
                throw new InvalidUserFieldException("The configured user field {$userField} is not provided in the request.");
            } //End if
        } //Loop ends

        //Register the user in Cognito
        $userKey = $request->has('username')?'username':'email';

        //Temporary Password paramter
        $password = $request->has('password')?$request['password']:null;

        return app()->make(AwsCognitoClient::class)->inviteUser(
            $request[$userKey], $password, $attributes,
            $clientMetadata, $messageAction,
            $isUserEmailForcedVerified,
            $groupname
        );
    } //Function ends

} //Trait ends
