<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Guards\Traits;

use Aws\Result as AwsResult;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\Authenticatable;

use Ellaisys\Cognito\AwsCognito;
use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoClientInterface;
use Ellaisys\Cognito\AwsCognitoClientManager;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;

use Ellaisys\Cognito\Http\Parser\ClaimSession;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;
use Ellaisys\Cognito\Validators\AwsCognitoTokenValidator;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

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
     * Set the token.
     *
     * @return $this
     */
    public function setToken()
    {
        $this->cognito->setClaim($this->claim)->storeToken();

        return $this;
    } //Function ends

    /**
     * Get the claim data.
     *
     * @return $claim
     */
    public function getClaim()
    {
        //Check for request having token
        if (! $this->cognito->parser()->setRequest($this->request)->hasToken()) {
            return null;
        } //End if

        if (! $this->cognito->parseToken()->authenticate()) {
            throw new NoLocalUserException();
        } //End if

        //Get claim
        $claim = $this->cognito->getClaim();
        if (empty($claim)) {
            throw new InvalidTokenException();
        } //End if
        return $claim;
    } //Function ends

    /**
     * Get the challenged claim.
     *
     * @return $claim
     */
    public function getChallengeData(string $key)
    {
        return $this->cognito->getChallengeData($key);
    } //Function ends

    /**
     * Save the challenged claim.
     *
     * @return $this
     */
    public function setChallengeData(string $key)
    {
        $this->cognito->setChallengeData($key, $this->challengeData);
        return $this;
    } //Function ends

    /**
     * Get the User Information from AWS Cognito
     *
     * @return  mixed
     */
    public function getRemoteUserData(string $username) {
        return $this->client->adminGetUser($username);
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
            Log::error('BaseCognitoGuard:setLocalUserData:Exception:');
            throw $e;
        } //End try-catch

        return $this->client->adminGetUser($username);
    } //Function ends

    /**
     * Validate the user credentials with AWS Cognito
     *
     * @return \Aws\Result
     */
    protected function hasValidAWSCredentials(Collection $credentials,
        CognitoAuthFlowTypes $authFlow): AwsResult {
        //Reset global variables
        $this->challengeName = null;
        $this->challengeData = null;
        $this->claim = null;
        $this->awsResult = null;

        //Authenticate the user with AWS Cognito
        $result = $this->client->authenticate(
            $authFlow,
            $credentials['email'],
            $credentials['password'] ?? null,
            $credentials['device_key'] ?? null,
            $credentials['challenge_name'] ?? null
        );

        return $this->processCognitoResponse($result, $credentials);
    } //Function ends

    /**
     * Process the AWS Cognito response for authentication and challenges.
     * @param \Aws\Result $result
     * @param \Illuminate\Support\Collection $data
     *
     * @return \Aws\Result
     */
    protected function processCognitoResponse(AwsResult $result, Collection $data): AwsResult
    {
        //Check if the result is an instance of AwsResult
        if (!empty($result) && $result instanceof AwsResult) {
            //Set value into class param
            $this->awsResult = $result;

            //Check in case of any challenge
            if (isset($result['ChallengeName'])) {
                $this->challengeName = $result['ChallengeName'];
                $this->challengeData = $this->handleCognitoChallenge($result, $data);
            } elseif (isset($result['AuthenticationResult'])) {
                //Create claim token
                $this->claim = new AwsCognitoClaim($result, null);
            } else {
                throw new AwsCognitoException(AwsCognitoException::COGNITO_AUTH_USER_UNAUTHORIZED);
            } //End if
        } else {
            throw new AwsCognitoException(AwsCognitoException::COGNITO_AUTH_USER_UNAUTHORIZED);
        } //End if

        return $result;
    } //Function ends

    /**
     * Handle Cognito Challenge
     * @param \Aws\Result $result
     * @param \Illuminate\Support\Collection $credentials
     * @return array
     */
    protected function handleCognitoChallenge(AwsResult $result, Collection $credentials): array
    {
        //Return value
        $returnValue = [];
        
        $challengeType = CognitoChallengeTypes::from($result['ChallengeName']);
        switch ($challengeType) {
            case CognitoChallengeTypes::PASSWORD_VERIFIER:
            case CognitoChallengeTypes::DEVICE_PASSWORD_VERIFIER:
                $returnValue = [
                    'session_token' => $credentials['session_token'] ?? null,
                ];
                break;

            case CognitoChallengeTypes::NEW_PASSWORD_REQUIRED:
            case CognitoChallengeTypes::SELECT_MFA_TYPE:
            case CognitoChallengeTypes::SOFTWARE_TOKEN_MFA:
            case CognitoChallengeTypes::SMS_MFA:
            case CognitoChallengeTypes::EMAIL_MFA:
            case CognitoChallengeTypes::DEVICE_SRP_AUTH:
            case CognitoChallengeTypes::SELECT_CHALLENGE:
            case CognitoChallengeTypes::SMS_OTP:
            case CognitoChallengeTypes::EMAIL_OTP:
            case CognitoChallengeTypes::WEB_AUTHN:
            default:
                $returnValue = [
                    'session_token' => isset($result['Session']) ? $result['Session'] : null
                ];
                break;
        } //End switch

        //Add username and challenge name into return value
        $returnValue = array_merge($returnValue, [
            'status' => 'challenge',
            'username' => $credentials['email'],
            'challenge_name' => $challengeType->value,
            'challenge_params' => isset($result['ChallengeParameters']) ? $result['ChallengeParameters'] : null,
            'available_challenges' => isset($result['AvailableChallenges']) ? $result['AvailableChallenges'] : null
        ]);

        return $returnValue;
    } //Function ends

    /**
     * Build the payload array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $paramUsername
     * @param  string  $paramPassword
     * @param  \Ellaisys\Cognito\Enums\CognitoAuthFlowTypes  $authFlow
     * @return array
     */
    final public function buildCognitoPayload(Collection $request,
        string $paramUsername, string $paramPassword,
        CognitoAuthFlowTypes $authFlow): Collection
    {
        $payload = [];

        //Add key fields
        $payload = array_merge($payload, [
            'remember' => $request->has('remember')?$request['remember']:false
            ]);
        
        //Get the configuration fields
        $userFields = array_filter(config('cognito.cognito_user_fields'), function($value) {
            return !empty($value);
        });

        if ($userFields) {
            //Iterate all the keys in the request
            $request->each(function($value, $key) use ($userFields, $paramUsername,
                $paramPassword, &$payload, $authFlow) {
                switch ($key) {
                    case $paramUsername:
                        $payload = array_merge($payload, ['email' => $value]);
                        break;
                    
                    case $paramPassword:
                        $payload = array_merge($payload, ['password' => $value]);
                        break;

                    case 'device_key':
                        $payload = array_merge($payload, ['device_key' => $value]);
                        break;

                    case 'challenge_name':
                        $payload = array_merge($payload, ['challenge_name' => $value]);
                        break;
                    
                    default:
                        if (array_key_exists($key, $userFields) ||
                            ($authFlow == CognitoAuthFlowTypes::USER_SRP_AUTH))
                        {
                            $payload = array_merge($payload, [$key => $value]);
                        } //End if
                        break;
                } //Switch end
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
                        throw new InvalidUserException();
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
        } catch (InvalidUserException | NoLocalUserException | Exception $e) {
            Log::error('BaseCognitoGuard:setLocalUserData:Exception');
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
                        $payload = array_merge($payload, [
                                $userFields[$value['Name']] => $value['Value']
                            ]);
                    } //End if

                    //Add user subject if exists
                    if ($value['Name'] == 'sub') {
                        $payload = array_merge($payload, [
                            config('cognito.user_subject_uuid') => $value['Value']
                        ]);
                    } //End if
                });
            } //End if
        } catch (Exception $e) {
            Log::error('BaseCognitoGuard:buildLocalUserPayload:Exception');
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
            $userModel = AwsCognito::$userModel;

            //Remove password from credentials if exists
            if (array_key_exists($keyPassword, $dataUser)) {
                unset($dataUser[$keyPassword]);
            } //End if
            
            //Create user into local DB, if not exists
            $user = $userModel::updateOrCreate($dataUser);
        } //End if

        return $user;
    } //Function ends

    /**
     * Attempt Challenge based Authentication
     */
    final public function attemptBaseChallenge(array $challenge): AwsResult
    {
        try {
            //Reset global variables
            $this->challengeName = null;
            $this->challengeData = null;
            $this->claim = null;
            $this->awsResult = null;

            $challengeName = CognitoChallengeTypes::from($challenge['challenge_name']);
            $session = $challenge['session'];
            $challengeValue = $challenge['challenge_value'];
            $username = $challenge['username'];

            //Get response for the challenge
            $result = $this->client->respondToAuthChallenge(
                    $challengeName, $session,
                    $challengeValue, $username
                );

            return $this->processCognitoResponse($result, collect([
                'email' => $username,
                'session_token' => $session
            ]));
        } catch(Exception $exception) {
            Log::error('BaseCognitoGuard:attemptBaseChallenge:Exception');
            throw $exception;
        } //Try-catch ends
    } //Function ends

    /**
     * Attempt Refresh Token based Authentication
     * @param  string  $refreshToken
     * @param  string  $username
     * @return AwsResult
     */
    final public function attemptRefreshToken(string $refreshToken, string $username,
        ?string $deviceKey = null, ?array $clientMetadata = null): AwsResult
    {
        try {
            // Refresh the token using AWS Cognito client
            $result = $this->client->refreshToken($refreshToken, $username, $deviceKey, $clientMetadata);

            return $this->processCognitoResponse($result, collect([
                'email' => $username,
                'refresh_token' => $refreshToken
            ]));
        } catch(Exception $exception) {
            Log::error('BaseCognitoGuard:refreshToken:Exception');
            throw $exception;
        } //Try-catch ends
    } //Function ends

} //Trait ends
