<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
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


use Ellaisys\Cognito\AwsCognito;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoClaim;

use Ellaisys\Cognito\Guards\Traits\BaseCognitoGuard;
use Ellaisys\Cognito\Guards\Traits\CognitoMFA;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserModelException;
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
        string $paramUsername='email', string $paramPassword='password')
    {
        try {
            $returnValue = false;
            $user = null;

            //convert to collection
            $request = collect($credentials);

            //Build the payload
            $payloadCognito = $this->buildCognitoPayload($request, $paramUsername, $paramPassword);

            //Fire event for authenticating
            $this->fireAttemptEvent($request->toArray(), $remember);

            //Check if the payload has valid AWS credentials
            $responseCognito = collect($this->hasValidAWSCredentials($payloadCognito));
            if ($responseCognito && (!empty($this->claim))) {
                //Process the claim
                if ($user = $this->processAWSClaim()) {
                    //Login user into the session
                    $this->login($user, $remember);

                    //Fire successful attempt
                    $this->fireLoginEvent($user, true);

                    $returnValue = true;
                } //End if
            } elseif ($responseCognito && $this->challengeName) {
                //Handle the challenge
                $returnValue = $this->handleAWSChallenge();
            } else {
                throw new AwsCognitoException('ERROR_AWS_COGNITO');
            } //End if
        } catch (CognitoIdentityProviderException $e) {
            Log::error('CognitoSessionGuard:attempt:CognitoIdentityProviderException:'.$e->getAwsErrorCode());

            //Handle the exception
            $returnValue = $this->handleCognitoException($e);
        } catch (NoLocalUserException | AwsCognitoException | Exception $e) {
            $exceptionClass = basename(str_replace('\\', DIRECTORY_SEPARATOR, get_class($e)));
            $exceptionCode = $e->getCode();
            $exceptionMessage = $e->getMessage().':(code:'.$exceptionCode.', line:'.$e->getLine().')';
            if ($e instanceof CognitoIdentityProviderException) {
                $exceptionCode = $e->getAwsErrorCode();
                $exceptionMessage = $e->getAwsErrorMessage().':'.$exceptionCode;
            } //End if
            Log::error('CognitoSessionGuard:attempt:'.$exceptionClass.':'.$exceptionMessage);

            //Find SQL Exception
            if (strpos($e->getMessage(), 'SQLSTATE') !== false) {
                throw new DBConnectionException();
            } //End if

            //Fire failed attempt
            if (!$returnValue) {
                $this->fireFailedEvent($user, $request->toArray());
            } //End if

            throw $e;
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

            //Get Session and store details
            $session = $this->getSession();
            $session->invalidate();
            $session->put('claim', json_decode(json_encode($this->claim), true));

            //Fire successful attempt
            $this->fireValidatedEvent($user);
            $this->fireAuthenticatedEvent($user);

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

        switch ($this->challengeName) {
            case 'SOFTWARE_TOKEN_MFA':
            case 'SMS_MFA':
                //Get Session and store details
                $session = $this->getSession();
                $session->invalidate();
                $session->put($this->challengeData['session_token'], json_decode(json_encode($this->challengeData), true));

                $returnValue = redirect(route(config('cognito.force_mfa_code_route_name'), [
                    'session_token' => $this->challengeData['session_token'],
                    'status' => $this->challengeData['status'],
                ]))
                    ->with('success', true)
                    ->with('force', true)
                    ->with('messaage', $this->challengeName);
                break;

            case AwsCognitoClient::NEW_PASSWORD_CHALLENGE:
            case AwsCognitoClient::RESET_REQUIRED_PASSWORD:
                if (config('cognito.force_password_change_web', false)) {
                    $returnValue =  redirect(route(config('cognito.force_redirect_route_name'), [
                        'challenge_name' => $this->challengeName,
                        'session_token' => $this->challengeData['session_token'],
                        'status' => $this->challengeData['status'],
                        'email' => $this->challengeData['username'],
                    ]))
                        ->with('success', true)
                        ->with('force', true)
                        ->with('messaage', $this->challengeName);
                } //End if
                break;
            
            default:
                //Do nothing
                break;
        } //End switch

        return $returnValue;
    } //Funtion ends


    /**
     * Handle the AWS Cognito Exception
     *
     * @param CognitoIdentityProviderException $e
     * @return mixed
     */
    private function handleCognitoException(CognitoIdentityProviderException $e) {
        if ($e instanceof CognitoIdentityProviderException) {
            //Set proper route
            if (!empty($e->getAwsErrorCode())) {
                switch ($e->getAwsErrorCode()) {
                    case 'PasswordResetRequiredException':
                        return redirect(route('cognito.form.reset.password.code'))
                            ->with('success', false)
                            ->with('force', true)
                            ->with('messaage', $e->getAwsErrorMessage())
                            ->with('aws_error_code', $e->getAwsErrorCode())
                            ->with('aws_error_message', $e->getAwsErrorMessage());
                        break;
                    
                    default:
                        throw $e;
                        break;
                } //End switch
            } //End if

            return $e->getAwsErrorCode();
        } else {
            return $e->getAwsErrorCode();
        } //End if
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

            //Get the claim from session
            $claim = $session->has('claim')?$session->get('claim'):null;
            if (empty($claim)) { $session->invalidate(); throw new HttpException(400, 'EXCEPTION_INVALID_CLAIM'); }

            $accessToken = (!empty($claim))?$claim['token']:null;
            if (empty($accessToken)) { throw new HttpException(400, 'EXCEPTION_INVALID_TOKEN'); }

            //Check if the token is empty
            if (!empty($accessToken)) {
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
                    $returnValue = $session->invalidate();
                } else {
                    //Remove the token from application storage
                    $returnValue = $session->invalidate();
                } //End if
            } else {
                //Remove the token from application storage
                $returnValue = $session->invalidate();
            } //End if
        } catch (Exception $e) {
            if ($forceForever) { return $session->invalidate(); }
            
            throw $e;
        } //try-catch ends

        return $returnValue;
    } //Function ends


    /**
     * Attempt MFA based Authentication
     */
    public function attemptMFA(array $challenge=[], bool $remember=false) {
        $returnValue = false;
        try {
            //Login with MFA Challenge
            $responseCognito = $this->attemptBaseMFA($challenge, $remember);
            if ($responseCognito && (!empty($this->claim))) {
                //Process the claim
                if ($user = $this->processAWSClaim()) {
                    //Login user into the session
                    $this->login($user, $remember);

                    //Fire successful attempt
                    $this->fireLoginEvent($user, true);

                    $returnValue = true;
                } //End if
            } elseif ($responseCognito && $this->challengeName) {
                //Handle the challenge
                $returnValue = $this->handleAWSChallenge();
            } else {
                throw new HttpException(400, 'ERROR_AWS_COGNITO');
            } //End if
        } catch(Exception $e) {
            throw $e;
        } //Try-catch ends

        return $returnValue;
    } //Function ends

} //Class ends
