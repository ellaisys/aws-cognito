<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Guards\Traits;

use Aws\Result as AwsResult;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\Authenticatable;

use Ellaisys\Cognito\AwsCognito;
use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoClientInterface;
use Ellaisys\Cognito\AwsCognitoClientManager;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Validators\AwsCognitoTokenValidator;

/**
 * Trait Base Cognito Guard
 */
trait BaseCognitoGuard
{

    /**
	 * Get the AWS Cognito object
     *
	 * @return \Ellaisys\Cognito\AwsCognito
	 */
    public function cognito() {
        return $this->cognito;
    } //Function ends


    /**
	 * Get the User Information from AWS Cognito
     *
	 * @return  mixed
	 */
    public function getRemoteUserData(string $username) {
        return $this->client->getUser($username);
    } //Function ends


    /**
	 * Set the User Information into the local DB
     *
	 * @return  mixed
	 */
    public function setLocalUserData(array $credentials) {
        try {
            //Get username key in the credentials
            $keyUsername = config('cognito.cognito_user_fields.email', 'email');

            //Get user from AWS Cognito
            $remoteUser = $this->getRemoteUserData($credentials[$keyUsername]);
            if (!empty($remoteUser)) {
                //Get user from presisting store
                $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);
            } else {
                throw new InvalidUserException('User not found in AWS Cognito');
            } //End if

            //Check if the user is not empty
            if (config('cognito.add_missing_local_user', false)) {
                    //Create user object from AWS Cognito
                    $user = [];

                    //Create user into local DB
                    $this->provider->createUser($user);
            } else {
                return null;
            } //End if
                        
        } catch (InvalidUserException | Exception $e) {
            Log::debug('BaseCognitoGuard:setLocalUserData:Exception:');
            throw $e;
        } //End try-catch

        return $this->client->getUser($username);
    } //Function ends


    /**
     * Validate the user credentials with AWS Cognito
     *
     * @return \Ellaisys\Cognito\AwsCognitoClient
     */
    protected function hasValidAWSCredentials(Collection $credentials) {
        //Reset global variables
        $this->challengeName = null;
        $this->challengeData = null;
        $this->claim = null;
        $this->awsResult = null;

        //Authenticate the user with AWS Cognito
        $result = $this->client->authenticate($credentials['email'], $credentials['password']);

        //Check if the result is an instance of AwsResult
        if (!empty($result) && $result instanceof AwsResult) {
            //Set value into class param
            $this->awsResult = $result;

            //Check in case of any challenge
            if (isset($result['ChallengeName'])) {
                $this->challengeName = $result['ChallengeName'];
                $this->challengeData = $this->handleCognitoChallenge($result, $credentials['email']);
            } elseif (isset($result['AuthenticationResult'])) {
                //Create claim token
                $this->claim = new AwsCognitoClaim($result, null);
            } else {
                $result = null;
            } //End if
        } //End if

        return $result;
    } //Function ends


    /**
     * handle Cognito Challenge
     */
    protected function handleCognitoChallenge(AwsResult $result, string $username) {

        //Return value
        $returnValue = null;
        
        switch ($result['ChallengeName']) {
            case 'SOFTWARE_TOKEN_MFA':
                $returnValue = [
                    'status' => $result['ChallengeName'],
                    'session_token' => $result['Session'],
                    'username' => $username
                ];
                break;

            case 'SMS_MFA':
            case 'SELECT_MFA_TYPE':
                $returnValue = [
                    'status' => $result['ChallengeName'],
                    'session_token' => $result['Session'],
                    'challenge_params' => $result['ChallengeParameters'],
                    'username' => $username
                ];
                break;

            default:
                if (in_array($result['ChallengeName'], config('cognito.forced_challenge_names'))) {
                    $returnValue = $result['ChallengeName'];
                } //End if
                break;
        } //End switch

        return $returnValue;
    } //Function ends


    /**
     * Build the payload array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $paramUsername
     * @param  string  $paramPassword
     * @return array
     */
    final public function buildCognitoPayload(Collection $request, $paramUsername='email', $paramPassword='password', bool $isCredential=false): Collection
    {
        $payload = [];

        //Get key fields
        if ($isCredential) {
            $rememberMe = $request->has('remember')?$request['remember']:false;
            $payload = array_merge($payload, ['remember' => $rememberMe]);
        } //End if
        
        //Get the configuration fields
        $userFields = array_filter(config('cognito.cognito_user_fields'), function($value) {
            return !empty($value);
        });

        if ($userFields) {
            //Iterate all the keys in the request
            $request->each(function($value, $key) use ($userFields, $paramUsername, $paramPassword, &$payload, $isCredential) {
                switch ($key) {
                    case $paramUsername:
                        $payload = array_merge($payload, ['email' => $value]);
                        break;
                    
                    case $paramPassword:
                        $payload = array_merge($payload, ['password' => $value]);
                        break;
                    
                    default:
                        if ($isCredential && array_key_exists($key, $userFields)) {
                            $payload = array_merge($payload, [$key => $value]);
                        }
                        break;
                } //Switch ends
            });
        } //End if

        return collect($payload);
    } //Function ends


    /**
     * Validate the user credentials with Local Data Store
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    protected function hasValidLocalCredentials(Collection $credentials): Authenticatable {
        try {
            $user = $this->provider->retrieveByCredentials($credentials->toArray());

            //Check if the user is not empty
            if (empty($user) && !($user instanceof Authenticatable)) {
                if (config('cognito.add_missing_local_user')) {
                    //Fetch user data from cognito
                    $userRemote = $this->getRemoteUserData($this->claim->getUsername());
                    if (empty($userRemote)) {
                        throw new Exception('User not found in AWS Cognito');
                    } //End if

                    //Create user object from cognito data
                    $payloadUser = $this->buildLocalUserPayload(collect($userRemote['UserAttributes']));
                    
                    //Create user into local DB
                    if ($this->createLocalUser($payloadUser->toArray())) {
                        $user = $this->provider->retrieveByCredentials($credentials->toArray());
                    } //End if
                } else {
                    throw new NoLocalUserException();
                } //End if
            } //End if
    
            return $user;
        } catch (NoLocalUserException | Exception $e) {
            Log::debug('BaseCognitoGuard:setLocalUserData:Exception');
            throw $e;
        } //End try-catch
    } //Function ends


    /**
     * Build the payload for Local DB
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $paramUsername
     * @param  string  $paramPassword
     * @return array
     */
    final public function buildLocalUserPayload(Collection $request): Collection
    {
        $payload = [];

        try {
            //Get the configuration fields
            $userFields = array_filter(config('cognito.cognito_user_fields'), function($value) {
                return !empty($value);
            });

            if ($userFields) {
                //Iterate all the keys in the request
                $request->each(function($value) use ($userFields, &$payload) {
                    if (array_key_exists($value['Name'], $userFields)) {
                        $payload = array_merge($payload, [$userFields[$value['Name']] => $value['Value']]);
                    } //End if

                    //Add user subject if exists
                    if ($value['Name'] == 'sub') {
                        $payload = array_merge($payload, [config('cognito.user_subject_uuid') => $value['Value']]);
                    } //End if
                });
            } //End if

        } catch (Exception $e) {
            Log::error($e->getMessage());
            throw $e;
        } //End try-catch

        return collect($payload);
    } //Function ends

    
    /**
     * Create a local user if one does not exist.
     *
     * @param  array  $credentials
     * @return mixed
     */
    final public function createLocalUser(array $dataUser, string $keyPassword='password')
    {
        $user = null;
        if (config('cognito.add_missing_local_user')) {
            //Get user model from configuration
            $userModel = config('cognito.sso_user_model');

            //Remove password from credentials if exists
            if (array_key_exists($keyPassword, $dataUser)) {
                unset($dataUser[$keyPassword]);
            } //End if
            
            //Create user into local DB, if not exists
            $user = $userModel::updateOrCreate($dataUser);
        } //End if

        return $user;
    } //Function ends

} //Trait ends
