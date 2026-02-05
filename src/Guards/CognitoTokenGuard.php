<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Guards;

use Aws\Result as AwsResult;
use Illuminate\Http\Request;
use Illuminate\Auth\TokenGuard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

use Ellaisys\Cognito\AwsCognito;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoClaim;

use Ellaisys\Cognito\Guards\Traits\BaseCognitoGuard;
use Ellaisys\Cognito\Guards\Traits\CognitoMFA;

use Exception;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserModelException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class CognitoTokenGuard extends TokenGuard
{

    use BaseCognitoGuard, CognitoMFA;

    /**
     * Username key
     *
     * @var  \string
     */
    protected $keyUsername;


    /**
     * @var  \AwsCognitoClient
     */
    protected $client;


    /**
     * The AwsCognito instance.
     *
     * @var \Ellaisys\Cognito\AwsCognito
     */
    protected $cognito;


    /**
     * The AwsCognito Claim token
     *
     * @var \Ellaisys\Cognito\AwsCognitoClaim|null
     */
    protected $claim;
    

    /**
     * @var Authentication Challenge
     */
    protected $challengeName;


    /**
     * @var AwsResult
     */
    protected $awsResult;


    /**
     * @var Challenge Data based on the challenge
     */
    protected $challengeData;


    /**
     * CognitoTokenGuard constructor.
     *
     * @param $callback
     * @param AwsCognitoClient $client
     * @param Request $request
     * @param UserProvider $provider
     */
    public function __construct(
        AwsCognito $cognito,
        AwsCognitoClient $client,
        Request $request,
        ?UserProvider $provider = null,
        ?string $keyUsername = null
    ) {
        $this->cognito = $cognito;
        $this->client = $client;
        $this->keyUsername = $keyUsername;

        parent::__construct($provider, $request);
    }


    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @param  bool   $remember
     * @throws
     * @return bool
     */
    public function attempt(array $request = [], $remember = false, string $paramUsername='email', string $paramPassword='password')
    {
        $returnValue = null;
        try {
            //convert to collection
            $request = collect($request);

            //Build the payload
            $payloadCognito = $this->buildCognitoPayload($request, $paramUsername, $paramPassword);

            //Check if the payload has valid AWS credentials
            $responseCognito = collect($this->hasValidAWSCredentials($payloadCognito));
            if ($responseCognito) {
                if ($this->claim) {
                    $credentials = collect([
                        config('cognito.user_subject_uuid') => $this->claim->getSub()
                    ]);

                    //Check if the user exists
                    $this->lastAttempted = $user = $this->hasValidLocalCredentials($credentials);

                    //Login the user into the token guard
                    $returnValue = $this->login($user);
                } elseif ($this->challengeName) {
                    //Get the key
                    $key = $this->challengeData['session_token'];

                    //Save the challenge data
                    $this->setChallengeData($key);

                    $returnValue = $this->challengeData;
                } else {
                    throw new AwsCognitoException();
                } //End if
            } else {
                throw new InvalidUserException();
            } //End if
        } catch (NoLocalUserException $e) {
            Log::error('CognitoTokenGuard:attempt:NoLocalUserException:'.$e->getMessage());
            throw $e;
        } catch (CognitoIdentityProviderException $e) {
            Log::error('CognitoTokenGuard:attempt:CognitoIdentityProviderException:'.$e->getAwsErrorCode());
            $returnValue = $e->getAwsErrorCode();

            //Set proper route
            if (!empty($e->getAwsErrorCode())) {
                $errorCode = 'CognitoIdentityProviderException';
                switch ($e->getAwsErrorCode()) {
                    case 'PasswordResetRequiredException':
                        $errorCode = 'cognito.validation.auth.reset_password';
                        break;

                    case 'NotAuthorizedException':
                        $errorCode = 'cognito.validation.auth.user_unauthorized';
                        break;
                    
                    default:
                        $errorCode = $e->getAwsErrorCode();
                        break;
                } //End switch

                $returnValue =  response()->json([
                    'error' => $errorCode,
                    'message' => $e->getAwsErrorMessage(),
                    'aws_error_code' => $e->getAwsErrorCode(),
                    'aws_error_message' => $e->getAwsErrorMessage()
                ], 400);
            } //End if

            return $returnValue;
        } catch (AwsCognitoException | InvalidUserException $e) {
            Log::error('CognitoTokenGuard:attempt:AwsCognitoException:'. $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('CognitoTokenGuard:attempt:Exception:'.$e->getMessage());
            throw $e;
        } //Try-catch ends

        return $returnValue;
    } //Function ends


    /**
     * Create a token for a user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     *
     * @return claim
     */
    private function login(Authenticatable $user)
    {
        if (!empty($this->claim)) {

            //Save the claim if it matches the Cognito Claim
            if ($this->claim instanceof AwsCognitoClaim) {
                //Set User
                $this->claim->setUser($user);

                //Set Token
                $this->setToken();
            } //End if

            //Set user
            $this->setUser($user);
        } //End if

        //Send claim object
        $claim = $this->claim;
        if ($claim && is_array($claim) && $claim['status']) {
            switch ($claim['status']) {
                case 'SOFTWARE_TOKEN_MFA':
                case 'SMS_MFA':
                    unset($claim['username']);
                    unset($claim['user']);
                    break;
                
                default:
                    # code...
                    break;
            } //Switch ends
        } //End if

        return $claim;
    } //Fucntion ends


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
     * Get the challenged claim.
     *
     * @return $this
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
     * Logout the user, thus invalidating the token.
     *
     * @param  bool  $forceForever
     *
     * @return void
     */
    public function logout(bool $forceForever = false)
    {
        $this->invalidate($forceForever);
        $this->user = null;
    } //Function ends


    /**
     * Invalidate the token.
     *
     * @param  bool  $forceForever
     *
     * @return \Ellaisys\Cognito\AwsCognito
     */
    public function invalidate(bool $forceForever = false)
    {
        try {
            //Get authentication token from request
            $accessToken = $this->cognito->getToken();

            //Revoke the token from AWS Cognito
            if ($this->client->signOut($accessToken)) {

                //Global logout and invalidate the Refresh Token
                if ($forceForever) {
                    //Get claim data
                    $data = $this->cognito->getClaim();
                    if ($data && ($dataClaim = $data['data'])) {
                        //Retrive the Refresh Token from the claim
                        $refreshToken = $dataClaim['RefreshToken'];

                        //Invalidate the Refresh Token
                        $this->client->revokeToken($refreshToken);
                    } //End if
                } //End if

                //Remove the token from application storage
                return $this->cognito->unsetToken($forceForever);
            } //End if
        } catch (Exception $e) {
            Log::error('CognitoTokenGuard:invalidate:Exception');
            throw $e;
        } //try-catch ends
    } //Function ends


    /**
     * Get the authenticated user.
     *
     * @return \Illuminate\Contracts\Auth\Authenticatable
     */
    public function user() {

        //Check if the user exists
        if (!is_null($this->user)) {
            return $this->user;
        } //End if

        //Retrieve token from request and authenticate
        return $this->getTokenForRequest();
    } //Function ends


    /**
     * Get the token for the current request.
     * @return string
     */
    public function getTokenForRequest () {
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
            return null;
        } //End if

        //Get user and return
        return $this->user = $this->provider->retrieveById($claim['sub']);
    } //Function ends


    /**
     * Get the user from the provider.
     * @return User
     */
    public function getUser (string $identifier) {
        return $this->provider->retrieveById($identifier);
    } //Function ends


    /**
     * Attempt MFA based Authentication
     *
     * @param  array  $challenge
     * @param  bool   $remember
     *
     * @throws
     *
     * @return bool
     */
    public function attemptMFA(array $challenge=[], bool $remember=false) {
        $returnValue = null;
        try {
            $responseCognito = $this->attemptBaseMFA($challenge, $remember);
            if ($responseCognito) {
                if ($this->claim) {
                    $credentials = collect([
                        config('cognito.user_subject_uuid') => $this->claim->getSub()
                    ]);

                    //Check if the user exists
                    $this->lastAttempted = $user = $this->hasValidLocalCredentials($credentials);

                    //Login the user into the token guard
                    $returnValue = $this->login($user);
                } elseif ($this->challengeName) {
                    $returnValue = $this->challengeData;
                } else {
                    throw new AwsCognitoException();
                } //End if
            } else {
                throw new InvalidUserException();
            } //End if
        } catch(AwsCognitoException | InvalidUserException | Exception $e) {
            Log::error('CognitoTokenGuard:attemptMFA:Exception');
            throw $e;
        } //Try-catch ends

        return $returnValue;
    } //Function ends

} //Class ends
