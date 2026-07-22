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

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Session\Session;
use Symfony\Component\HttpFoundation\Request;

use Illuminate\Database\Eloquent\Model;
use Ellaisys\Cognito\Http\Parser\ClaimSession;

use Ellaisys\Cognito\AwsCognito;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoClaim;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;

use Ellaisys\Cognito\Guards\Traits\BaseCognitoGuard;
use Ellaisys\Cognito\Guards\Traits\CognitoMFA;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserModelException;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;
use Ellaisys\Cognito\Exceptions\DBConnectionException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class CognitoSessionGuard extends SessionGuard implements StatefulGuard
{
    use BaseCognitoGuard, CognitoMFA;

    /**
     * Username key
     *
     * @var  \string
     */
    protected $keyUsername;

    /**
     * @var AwsCognitoClient
     */
    protected $client;

    /**
     * @var Request Session
     */
    protected $session;

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
     * CognitoSessionGuard constructor.
     *
     * @param string $name
     * @param AwsCognitoClient $client
     * @param UserProvider $provider
     * @param Session $session
     * @param Request $request

     */
    public function __construct(
        string $name,
        AwsCognito $cognito,
        AwsCognitoClient $client,
        UserProvider $provider,
        Session $session,
        Request $request,
        string $keyUsername = 'email'
    ) {
        $this->cognito = $cognito;
        $this->client = $client;
        $this->session = $session;
        $this->awsResult = null;
        $this->keyUsername = $keyUsername;

        parent::__construct($name, $provider, $session, $request);
    }

    /**
     * Attempt to authenticate an existing user using the credentials
     * using Cognito
     *
     * @param  array  $credentials
     * @param  bool   $remember
     * @throws
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false,
        string $paramUsername='email', ?string $paramPassword='password',
        ?CognitoAuthFlowTypes $authFlow = CognitoAuthFlowTypes::USER_PASSWORD_AUTH)
    {
        // Initialize return value
        $returnValue = null;
        $user = null;

        try {
            //convert to collection
            $request = collect($credentials);

            //Build the payload
            $payloadCognito = $this->buildCognitoPayload($request, $paramUsername,
                $paramPassword, $authFlow);

            //Fire event for authenticating
            $this->fireAttemptEvent($credentials, $remember);

            //Check if the payload has valid AWS credentials
            $responseCognito = $this->hasValidAWSCredentials($payloadCognito, $authFlow);

            // Process the AWS Cognito response
            $returnValue = $this->processAwsResult($responseCognito, true, $remember);
        } catch (Exception $exception) {
            Log::error('CognitoSessionGuard:attempt:Exception');

            // Fire failed attempt
            $this->fireFailedEvent($user, $credentials);

            //Find SQL Exception
            if (strpos($exception->getMessage(), 'SQLSTATE') !== false) {
                throw new DBConnectionException();
            } //End if

            throw $exception;
        } //Try-catch ends
        
        return $returnValue;
    } //Function ends

    /**
     * Process the AWS Claim and Authenticate the user with the local database
     *
     * @return Authenticatable
     */
    private function processAWSClaim(): Authenticatable {
        $credentials = collect([
            config('cognito.user_subject_uuid') => $this->claim->getSub()
        ]);

        //Check if the user exists
        $this->lastAttempted = $user = $this->hasValidLocalCredentials($credentials);
        if (!empty($user) && ($user instanceof Authenticatable)) {

            //Save the user data into the claim
            $this->claim->setUser($user);

            //Set Token
            $this->setToken();

            /*
             * Note: We do not flush the session here, because other guards may be
             * sharing this session. Instead, we just regenerate the session id
             * (to keep session-fixation protection) without flushing the session.
             */
            $session = $this->getSession();
            $session->migrate(true);
            $session->put(
                ClaimSession::SESSION_KEY,
                json_decode(json_encode($this->claim), true)
            );

            return $user;
        } else {
            throw new NoLocalUserException();
        } //End if
    } //Function ends

    /**
     * Handle the AWS Challenge
     *
     * @return mixed
     */
    private function handleAWSChallenge() {
        $returnValue = null;

        $challengeType = CognitoChallengeTypes::from($this->challengeName);
        switch ($challengeType) {
            case CognitoChallengeTypes::NEW_PASSWORD_REQUIRED:
            case CognitoChallengeTypes::RESET_REQUIRED:
                if (config('cognito.force_password_change_web', false)) {
                    $returnValue =  redirect(route(config('cognito.force_redirect_route_name'), [
                        'challenge_name' => $this->challengeName,
                        'session_token' => $this->challengeData['session_token'],
                        'status' => $this->challengeData['status'],
                        'email' => $this->challengeData['username'],
                    ]))
                        ->with('success', 'success')
                        ->with('force', true)
                        ->with('message', $this->challengeName);
                } //End if
                break;

            case CognitoChallengeTypes::SOFTWARE_TOKEN_MFA:
            case CognitoChallengeTypes::SMS_MFA:
            case CognitoChallengeTypes::DEVICE_SRP_AUTH:
            case CognitoChallengeTypes::DEVICE_PASSWORD_VERIFIER:
            case CognitoChallengeTypes::PASSWORD_VERIFIER:
            default:
                //Invalidate the session
                $session = $this->getSession();
            
                /*
                 * Note: We do not flush the session here, because other guards may be
                 * sharing this session. Instead, we just regenerate the session id
                 * (to keep session-fixation protection) without flushing the session.
                 */
                $session->migrate(true);

                $returnValue = $this->challengeData;
                break;
        } //End switch

        return $returnValue;
    } //Function ends

    /**
     * Logout the user, thus invalidating the session.
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
    public function invalidate($forceForever = false)
    {
        //Return Value
        $returnValue = null;

        try {
            //Get authentication token from session
            $session = $this->getSession();

            // Call the parents logout method
            parent::logout();

            //Get the claim from session
            $claim = $session->has(ClaimSession::SESSION_KEY)?$session->get(ClaimSession::SESSION_KEY):null;
            if (empty($claim)) { $this->forgetCognitoSession($session); throw new InvalidTokenException('EXCEPTION_INVALID_CLAIM'); }

            $accessToken = (!empty($claim))?$claim['token']:null;
            if (empty($accessToken)) {
                $session->invalidate();
                throw new InvalidTokenException('EXCEPTION_INVALID_TOKEN');
            } else {
                //Revoke the token from AWS Cognito
                if ($this->client->signOut($accessToken)) {

                    //Global logout and invalidate the Refresh Token
                    if ($forceForever) {
                        //Get claim data
                        $dataClaim = (!empty($claim))?$claim['data']:null;
                        if ($dataClaim) {
                            //Retrive the Refresh Token from the claim
                            $refreshToken = $dataClaim['RefreshToken'];

                            //Invalidate the Refresh Token
                            $this->client->revokeToken($refreshToken);
                        } //End if
                    } //End if

                    //Remove the token from application storage
                    $returnValue = $this->forgetCognitoSession($session);
                } else {
                    //Remove the token from application storage
                    $returnValue = $this->forgetCognitoSession($session);
                } //End if
            } //End if
        } catch (Exception $e) {
            if ($forceForever) { return $this->forgetCognitoSession($session); }

            throw $e;
        } //try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Sign the Cognito user out of the (possibly shared) session.
     *
     * Forgets only this guard's keys - the Cognito claim and the guard's own
     * auth key - and regenerates the session id, rather than flushing the whole
     * session via invalidate(). The session is shared with any other guard
     * (e.g. a separate admin guard), so a full flush would log those guards out
     * too; this leaves their data intact.
     *
     * @param  \Illuminate\Contracts\Session\Session  $session
     * @return bool
     */
    private function forgetCognitoSession($session): bool
    {
        $session->forget(ClaimSession::SESSION_KEY);
        $session->forget($this->getName());

        return $session->migrate(true);
    } //Function ends

    /**
     * Attempt Challenge based Authentication
     *
     * @param  array  $challenge
     * @param  bool   $remember
     *
     * @throws
     *
     * @return mixed
     */
    public function attemptChallengeAuth(array $challenge, bool $remember=false): mixed
    {
        // Initialize variables
        $returnValue = null;

        try {
            //Login with Challenge
            $responseCognito = $this->attemptBaseChallenge($challenge);

            // Process the AWS Cognito response
            $returnValue = $this->processAwsResult($responseCognito, true, $remember);
        } catch(Exception $e) {
            Log::error('CognitoSessionGuard:attemptChallengeAuth:Exception');
            throw $e;
        } //Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Refresh the token using the provided refresh token and username.
     * @param  string  $refreshToken
     * @param  string  $username
     * @param string|null  $deviceKey
     * @param array|null  $clientMetadata
     *
     * @throws
     *
     * @return mixed
     */
    public function refreshToken(string $refreshToken, string $username,
        ?string $deviceKey = null, ?array $clientMetadata = null): mixed
    {
        $returnValue = null;
        try {
            // Attempt to refresh the token using AWS Cognito
            $responseCognito = $this->attemptRefreshToken($refreshToken, $username, $deviceKey, $clientMetadata);

            // Process the AWS Cognito response
            $returnValue = $this->processAwsResult($responseCognito, false, false);
        } catch(Exception $exception) {
            Log::error('CognitoSessionGuard:refreshToken:Exception');
            throw $exception;
        } //Try-catch ends

        return $returnValue;
    } //Function ends

    /**
     * Process the AWS Cognito response and return the appropriate result.
     * @param AwsResult  $awsResult
     * @param bool  $raiseEvent
     *
     * @throws
     *
     * @return mixed
     */
    private function processAwsResult(AwsResult $awsResult, 
        bool $raiseEvent = true, bool $remember=false): mixed
    {
        // Initialize variables
        $returnValue = null;

        try {
            if ($awsResult && $this->claim) {
                //Process the claim
                if ($user = $this->processAWSClaim()) {
                    //Login user into the session
                    $this->login($user, $remember);

                    if ($raiseEvent) {
                        //Fire events for validated and authenticated
                        $this->fireValidatedEvent($user);
                        $this->fireAuthenticatedEvent($user);
                        $this->fireLoginEvent($user, $remember);
                    } //End if

                    $returnValue = $this->claim;
                } //End if
            } elseif ($awsResult && $this->challengeName) {
                //Handle the challenge
                $returnValue = $this->handleAWSChallenge();
            } else {
                throw new AwsCognitoException(AwsCognitoException::COGNITO_AUTH_USER_UNAUTHORIZED);
            } //End if
        } catch(Exception $exception) {
            Log::error('CognitoSessionGuard:processAwsResult:Exception');
            throw $exception;
        } //Try-catch ends

        return $returnValue;
    } //Function ends

} //Class ends
