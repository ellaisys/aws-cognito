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

use Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoUserPool;

use Ellaisys\Cognito\Events\Auth\PreRegistrationEvent;
use Ellaisys\Cognito\Events\Auth\PostRegistrationEvent;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait RegistersUsers
{
    use BaseAuthTrait;

    /**
     * Private variable for Registration Type
     */
    private string $registrationType = 'register';

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
     * Private variable for success message
     *
     * @var string
     */
    private string $messageKey = 'messages.auth.registration_success';

    /**
     * Handle a registration invite for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function invite(Request $request, ?array $clientMetadata = null): mixed
    {
        $this->registrationType = 'invite';
        $this->redirectTo = config('cognito.routes.web.home_page');
        $this->messageKey = 'messages.auth.invitation_success';

        return $this->register(
            $request, $clientMetadata, true
        );

    } //Function ends

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request, ?array $clientMetadata = null,
        bool $usePassedRegistrationType = false)
    {
        try {
            // Initialize variables
            $returnValue = null;
            $cognitoRegistered=false;
            $user = null;

            //Set the registration type
            if (!$usePassedRegistrationType) {
                $this->registrationType = config('cognito.registration_type', 'register');
            } //End if

            //Redirect to verification page if registration type is register
            if ($this->registrationType=='register') {
                $this->redirectTo = config('cognito.routes.web.register_verify_page');
            } //End if

            //Raise pre registration event
            $this->callPreRegistrationEvent($request);

            //Get the password policy
            $this->passwordPolicy = app()->make(AwsCognitoUserPool::class)->getPasswordPolicy(true);

            //Validate request
            $validator = Validator::make(
                $request->all(), $this->rulesRegisterUser(), [
                'regex' => 'Must contain atleast '.$this->passwordPolicy['message'],
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create data to save
            $payload = $request->all();

            //Create credentials object
            $collection = collect($payload);

            //Register User in Cognito
            $cognitoRegistered=$this->createCognitoUser(
                $collection, $clientMetadata,
                config('cognito.default_user_group', null)
            );

            if (!empty($cognitoRegistered)) {
                $user = $this->createUserInDatastore($payload, $cognitoRegistered->toArray());
            } else {
                throw new AwsCognitoException('User registration failed in Cognito.');
            } //End if

            //Raise post registration event
            $this->callPostRegistrationEvent($request, $user);

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $user;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($user);
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('status', 'success')
                    ->with('message', trans($this->messageKey));
            } //End if

            return $returnValue;
        } catch (Exception $e) {
            Log::error('RegistersUsers:register:Exception');
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
    public function createCognitoUser(Collection $request,
        ?array $clientMetadata = null, ?string $groupname = null)
    {
        //Get the configuration for new user invitation message action.
        $messageAction = config('cognito.new_user_message_action', null);

        //Build the Cognito user payload
        $attributes = $this->buildCognitoUserPayload($request);

        //Register the user in Cognito
        $userKey = $request->has('username')?'username':'email';

        //Password parameter
        $password = null;
        if (config('cognito.force_new_user_password', true)) {
            $password = $request->has($this->paramPassword)?$request[$this->paramPassword]:null;
        } // End if

        if ($this->registrationType == 'invite') {
            //Invite User
            return app()->make(AwsCognitoClient::class)->inviteUser(
                $request[$userKey], $password, $attributes,
                $clientMetadata, $messageAction,
                $groupname
            );
        } elseif ($this->registrationType == 'register') {
            //Password is required for register
            if (empty($password)) {
                $password = $this->generateRandomPassword();
            } //End if

            //Register User
            return app()->make(AwsCognitoClient::class)->register(
                $request[$userKey], $password, $attributes,
                $clientMetadata, $groupname
            );
        } else {
            throw new HttpException(400, 'Invalid registration type.');
        } //End if
    } //Function ends

    /**
     * Method to determine if the response should be in json format based on the request type
     *
     * @param Request $request
     *
     * @return bool
     */
    private function buildCognitoUserPayload(Collection $request): array
    {
        $attributes = [];

        //Get the registeration fields
        $userFields = config('cognito.cognito_user_fields');

        //Iterate the fields
        foreach ($userFields as $key => $userField) {
            if ($userField!=null && $request->has($userField)) {
                $attributes[$key] = $request->get($userField);
            } //End if
        } //Loop ends

        return $attributes;
    } //Function ends

    /**
     * Method to create a new user instance after a valid registration.
     *
     * @param  array  $payload
     * @param  array  $cognitoRegistered
     * @return array
     */
    protected function createUserInDatastore(array $payload, array $cognitoRegistered): array
    {
        try {
            //Get the user model
            $model = Auth::getProvider()->getModel();

            //Set the register type and registered at fields
            if (method_exists($model, 'hasRegistrationTrait')) {
                $payload = array_merge($payload, [
                    'register_type' => $this->registrationType,
                    'registered_at' => now(),
                ]);
            } //End if

            //Build the local user data by adding the cognito registered data to it
            $payload = array_merge($payload, $this->buildUserPayloadForDatastore($payload, $cognitoRegistered));

            //Create the user in local database
            $user = $model::create($payload);
            return $user->toArray();
        } catch (Exception $e) {
            Log::error('RegistersUsers:createUserInDatastore:Exception');
            throw $e;
        } //End try
    } //Function ends

    /**
     * Method to build the local user data by adding the cognito registered data to it
     *
     * @param array $payload
     * @param array $cognitoRegistered
     * @return array
     */
    private function buildUserPayloadForDatastore(array $payload, array $cognitoRegistered): array
    {
        try {
            //Remove the password
            if(!empty($payload[$this->paramPassword])) {
                unset($payload[$this->paramPassword]);
            } //End if

            //Add cognito data to user data
            if ($this->registrationType=='invite') {
                $payload = array_merge(
                        $payload,
                        $this->buildInvitePayloadForDatastore($payload, $cognitoRegistered)
                    );
            } else {
                $payload = array_merge(
                        $payload,
                        $this->buildRegisterPayloadForDatastore($payload, $cognitoRegistered)
                    );
            } //End if

            return $payload;
        } catch (Exception $e) {
            Log::error('RegistersUsers:buildUserPayloadForDatastore:Exception');
            throw $e;
        } //End try
    } //Function ends

    /**
     * Add invite attributes to payload
     *
     * @param array $payload
     * @param array $cognitoRegistered
     * @return array
     */
    private function buildInvitePayloadForDatastore(array $payload, array $cognitoRegistered): array
    {
        if (!isset($cognitoRegistered['User'])) {
            return [];
        } //End if

        $cognitoUser = $cognitoRegistered['User'];
        $cognitoAttributes = $cognitoUser['Attributes'] ?? [];

        if (!is_array($cognitoAttributes) || count($cognitoAttributes) === 0) {
            return [];
        } //End if

        //Get the user model
        $model = Auth::getProvider()->getModel();

        //Get the registeration fields
        $userFields = config('cognito.cognito_user_fields');

        foreach ($cognitoAttributes as $cognitoAttribute) {
            $key = $cognitoAttribute['Name'];
            if (strtolower($key) === 'sub' && (method_exists($model, 'hasSubTrait'))) {
                $key = config('cognito.user_subject_uuid', 'sub');
            } elseif (array_key_exists($key, $userFields)) {
                $key = $userFields[$key];
            } else {
                continue; // Skip attributes that are not mapped
            } //End if

            $payload[$key] = $cognitoAttribute['Value'];
        } //End foreach

        return $payload;
    } //Function ends

    /**
     * Add registration attributes to payload
     *
     * @param array $payload
     * @param array $cognitoRegistered
     * @return array
     */
    private function buildRegisterPayloadForDatastore(array $payload, array $cognitoRegistered): array
    {
        //Get the user model
        $model = Auth::getProvider()->getModel();

        if (method_exists($model, 'hasSubTrait')) {
            $payload = array_merge($payload, [
                config('cognito.user_subject_uuid') => $cognitoRegistered['UserSub']
            ]);
        } //End if

        return $payload;
    } //Function ends

    /**
     * Get the registration validation rules.
     *
     * @return array
     */
    public function rulesRegisterUser()
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
                            $rules = array_merge($rules, [ $value => 'required|regex:/^\+[1-9]\d{1,14}$/']);
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
            Log::error('RegistersUsers:rulesRegisterUser:Exception');
            throw $e;
        } //End try
    } //Function ends

    /**
     * Method to raise the pre registration event
      *
      * @param  \Illuminate\Http\Request  $request
      * @return void
      */
    protected function callPreRegistrationEvent(Request $request): void
    {
        //Raise pre registration event
        event(new PreRegistrationEvent(
            $this->registrationType,
            $request->except('password'),
            $request->ip()
        ));
    } //Function ends

    /**
     * Method to raise the post registration event
      *
      * @param  \Illuminate\Http\Request  $request
      * @param  array|null  $user
      * @return void
      */
    protected function callPostRegistrationEvent(Request $request, ?array $user): void
    {
        //Raise post registration event
        event(new PostRegistrationEvent(
            $this->registrationType,
            $user,
            $request->except('password'),
            $request->ip()
        ));
    } //Function ends

} //Trait ends
