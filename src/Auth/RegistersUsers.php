<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Application;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoUserPool;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait RegistersUsers
{
    /**
     * Private variable for Registration Type
     */
    private $registrationType = 'register';

    /**
     * private variable for password policy
     */
    private $passwordPolicy = null;

    /**
     * Passed params
     */
    private $paramUsername = 'email';
    private $paramPassword = 'password';


    /**
     * Handle a registration invite for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function invite(Request $request, ?array $clientMetadata = null)
    {
        $this->registrationType = 'invite';
        return $this->register($request, $clientMetadata, true);
    } //Function ends


    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request, ?array $clientMetadata = null,
        bool $ignoreConfigRegistrationType = false)
    {
        $cognitoRegistered=false;
        $user = [];

        try {
            //Set the registration type
            if (!$ignoreConfigRegistrationType) {
                $this->registrationType = config('cognito.registration_type', 'register');
            } //End if

            //Get the password policy
            $this->passwordPolicy = app()->make(AwsCognitoUserPool::class)->getPasswordPolicy(true);

            //Validate request
            $validator = Validator::make($request->all(), $this->rulesRegisterUser(), [
                'regex' => 'Must contain atleast '.$this->passwordPolicy['message'],
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create data to save
            $data = $request->all();

            //Create credentials object
            $collection = collect($data);

            //Register User in Cognito
            $cognitoRegistered=$this->createCognitoUser($collection, $clientMetadata, config('cognito.default_user_group', null));            
            if ($cognitoRegistered) {
                //Remove the password
                if(!empty($data[$this->paramPassword])) {
                    unset($data[$this->paramPassword]);
                } //End if

                //Add cognito data to user data
                $cognitoUser = $cognitoRegistered['User'];
                if ($cognitoUser) {
                    $cognitoAttributes = $cognitoUser['Attributes'];
                    if ($cognitoAttributes && is_array($cognitoAttributes) && count($cognitoAttributes)>0) {
                        foreach ($cognitoAttributes as $cognitoAttribute) {
                            $data[$cognitoAttribute['Name']] = $cognitoAttribute['Value'];
                        } //End foreach
                    } //End if                     
                } //End if

                //Create user in local store
                $user = $this->create($data);
            } //End if

            // Return with user data
            return ($request->expectsJson() || $request->isJson())
                ? new JsonResponse($user, 200)
                : redirect($this->redirectPath());
        } catch (Exception $e) {
            throw $e;
        } //End try 
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
    public function createCognitoUser(Collection $request, ?array $clientMetadata = null, ?string $groupname = null)
    {
        //Initialize Cognito Attribute array
        $attributes = [];

        //Get the configuration for new user invitation message action.
        $messageAction = config('cognito.new_user_message_action', null);

        //Get the registeration fields
        $userFields = config('cognito.cognito_user_fields');

        //Iterate the fields
        foreach ($userFields as $key => $userField) {
            if ($userField!=null) {
                if ($request->has($userField)) {
                    $attributes[$key] = $request->get($userField);
                } else {
                    Log::error('RegistersUsers:createCognitoUser:InvalidUserFieldException');
                    Log::error("The configured user field {$userField} is not provided in the request.");
                    throw new InvalidUserFieldException("The configured user field {$userField} is not provided in the request.");
                } //End if
            } //End if
        } //Loop ends

        //Register the user in Cognito
        $userKey = $request->has('username')?'username':'email';

        //Password parameter
        $password = null;
        if (config('cognito.force_new_user_password', true)) {
            $password = $request->has($this->paramPassword)?$request[$this->paramPassword]:null;
        }// End if

        switch ($this->registrationType) {
            case 'invite':
                //Invite User
                return app()->make(AwsCognitoClient::class)->inviteUser(
                    $request[$userKey], $password, $attributes,
                    $clientMetadata, $messageAction,
                    $groupname
                );
                break;

            case 'register':
            default:
                //Password is required for register
                if (empty($password)) {
                    //Laravel versions prior to 10 do not have Str::password method
                    if (version_compare(Application::VERSION, '10.0.0', '<')) {
                        $password = Str::random(10).'1A!';
                    } else {
                        $password = Str::password(12);
                    } //End if
                } //End if

                //Register User
                return app()->make(AwsCognitoClient::class)->register(
                    $request[$userKey], $password, $attributes,
                    $clientMetadata, $groupname
                );
                break;
        } //End switch

    } //Function ends


    /**
     * Get the registration validation rules.
     *
     * @return array
     */
    protected function rulesRegisterUser()
    {
        $rules=[];

        try {
            //Get the registeration fields
            $userFields = config('cognito.cognito_user_fields');
            foreach ($userFields as $key => $value) {
                if ($value!=null) {
                    switch ($key) {
                        case 'email':
                            $rules = array_merge($rules, [ $value => 'required|email']);
                            break;

                        case 'phone_number':
                            $rules = array_merge($rules, [ $value => 'required']);
                            break;
                        
                        default:
                            $rules = array_merge($rules, [ $value => 'required']);
                            break;
                    } //End switch
                } //End if
            } //Loop ends

            //Check the new user password config
            if (config('cognito.force_new_user_password', true)) {
                $rules = array_merge($rules, [ $this->paramPassword => 'required|confirmed|regex:'.$this->passwordPolicy['regex']]);
            } //End if

            //Check the MFA setup config
            if (config('cognito.mfa_setup')=="MFA_ENABLED" && empty($userFields['phone_number'])) {
                throw new HttpException(400, 'ERROR_MFA_ENABLED_PHONE_MISSING');
            } //End if

            return $rules;
        } catch (Exception $e) {
            throw $e;
        } //End try
    } //Function ends

} //Trait ends
