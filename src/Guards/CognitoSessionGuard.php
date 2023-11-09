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
     * @var Authentication Challenge
     */
    protected $challengeName;


    /**
     * @var AwsResult
     */
    protected $awsResult;


    /**
     * @var Challenge Data based on 
     */
    protected $challengeData;


    /**
     * CognitoSessionGuard constructor.
     * 
     * @param string $name
     * @param AwsCognitoClient $client
     * @param UserProvider $provider
     * @param Session $session
     * @param null|Request $request

     */
    public function __construct(
        string $name,
        AwsCognito $cognito,
        AwsCognitoClient $client,
        UserProvider $provider,
        Session $session,
        ?Request $request = null,
        string $keyUsername = 'email'
    ) {
        $this->cognito = $cognito;
        $this->client = $client;
        $this->awsResult = null;
        $this->keyUsername = $keyUsername;

        parent::__construct($name, $provider, $session, $request);
    }


    /**
     * @param mixed $user
     * @param array $credentials
     * @return bool
     * @throws InvalidUserModelException
     */
    protected function hasValidCredentials($user, $credentials)
    {
        $result = $this->client->authenticate($credentials['email'], $credentials['password']);

        if (!empty($result) && $result instanceof AwsResult) {
            //Set value into class param
            $this->awsResult = $result;

            //Check in case of any challenge
            if (isset($result['ChallengeName'])) {

                //Set challenge into class param
                $this->challengeName = $result['ChallengeName'];
                switch ($result['ChallengeName']) {
                    case 'SOFTWARE_TOKEN_MFA':
                        $this->challengeData = [
                            'status' => $result['ChallengeName'],
                            'session_token' => $result['Session'],
                            'username' => $credentials[$this->keyUsername],
                            'user' => serialize($user)
                        ];
                        break;

                    case 'SMS_MFA':
                        $this->challengeData = [
                            'status' => $result['ChallengeName'],
                            'session_token' => $result['Session'],
                            'challenge_params' => $result['ChallengeParameters'],
                            'username' => $credentials[$this->keyUsername],
                            'user' => serialize($user)
                        ];
                        break;

                    default:
                        if (in_array($result['ChallengeName'], config('cognito.forced_challenge_names'))) {
                            $this->challengeName = $result['ChallengeName'];
                        } //End if
                        break;
                } //End switch
            } //End if

            return ($user instanceof Authenticatable);
        } //End if

        return false;
    } //Function ends


    /**
     * Attempt to authenticate an existing user using the credentials
     * using Cognito
     *
     * @param  array  $credentials
     * @param  bool   $remember
     * @throws
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        try {
            //Fire event for authenticating
            $this->fireAttemptEvent($credentials, $remember);

            //Get user from presisting store
            $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

            //Check if the user exists in local data store
            if (empty($user) && !($user instanceof Authenticatable)) {
                throw new NoLocalUserException();
            } //End if

            //Authenticate with cognito
            if ($this->hasValidCredentials($user, $credentials)) {
                if (!empty($this->challengeName)) {
                    switch ($this->challengeName) {
                        case 'SOFTWARE_TOKEN_MFA':
                        case 'SMS_MFA':
                            //Get Session and store details
                            $session = $this->getSession();
                            $session->invalidate();
                            $session->put($this->challengeData['session_token'], json_decode(json_encode($this->challengeData), true));

                            return redirect(route(config('cognito.force_mfa_code_route_name'), [
                                'session_token' => $this->challengeData['session_token'],
                                'status' => $this->challengeData['status'],
                            ]))
                                ->with('success', true)
                                ->with('force', true)
                                ->with('messaage', $this->challengeName);
                            break;
    
                        case AwsCognitoClient::NEW_PASSWORD_CHALLENGE:
                        case AwsCognitoClient::RESET_REQUIRED_PASSWORD:
                            $this->login($user, $remember);

                            if (config('cognito.force_password_change_web', false)) {
                                return redirect(route(config('cognito.force_redirect_route_name')))
                                    ->with('success', true)
                                    ->with('force', true)
                                    ->with('messaage', $this->challengeName);
                            } //End if
                            break;
                        
                        default:
                            if (in_array($this->challengeName, config('cognito.forced_challenge_names'))) {
                                $this->challengeName = $result['ChallengeName'];
                            } //End if
                            break;
                    } //End switch      
                } else { 
                    //Create Claim for confirmed users and store into session
                    if (!empty($this->awsResult)) {
                        //Create claim token
                        $claim = new AwsCognitoClaim($this->awsResult, $user, $credentials[$this->keyUsername]);

                        //Get Session and store details
                        $session = $this->getSession();
                        $session->invalidate();
                        $session->put('claim', json_decode(json_encode($claim), true));

                        $this->login($user, $remember);

                        //Fire successful attempt
                        $this->fireValidatedEvent($user);
                        $this->fireAuthenticatedEvent($user);  
                    } else {
                        throw new HttpException(400, 'ERROR_AWS_COGNITO');
                    } //End if                 
                } //End if

                return true;
            } //End if

            //Fire failed attempt
            $this->fireFailedEvent($user, $credentials);

            return false;
        } catch (NoLocalUserException $e) {
            Log::error('CognitoSessionGuard:attempt:NoLocalUserException:'.$e->getMessage());

            //Fire failed attempt
            $this->fireFailedEvent($user, $credentials);

            throw $e;
        } catch (CognitoIdentityProviderException $e) {
            Log::error('CognitoSessionGuard:attempt:CognitoIdentityProviderException:'.$e->getAwsErrorCode());

            //Fire failed attempt
            $this->fireFailedEvent($user, $credentials);

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
        } catch (AwsCognitoException $e) {
            Log::error('CognitoSessionGuard:attempt:AwsCognitoException:'.$e->getMessage());

            //Fire failed attempt
            $this->fireFailedEvent($user, $credentials);

            throw $e;
        } catch (Exception $e) {
            Log::error('CognitoSessionGuard:attempt:Exception:'.$e->getMessage());

            //Fire failed attempt
            if (!empty($user)) {
                $this->fireFailedEvent($user, $credentials);
            } //End if

            //Find SQL Exception
            if (strpos($e->getMessage(), 'SQLSTATE') !== false) {
                throw new DBConnectionException();
            } //End if

            throw $e;
        } //Try-catch ends
    } //Function ends


    /**
     * Logout the user, thus invalidating the token.
     *
     * @param  bool  $forceForever
     *
     * @return void
     */
    public function logout($forceForever = false)
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
                    return $session->invalidate();
                } else {
                    //Remove the token from application storage
                    return $session->invalidate();
                } //End if                
            } else {
                //Remove the token from application storage
                return $session->invalidate();
            } //End if
        } catch (Exception $e) {
            if ($forceForever) { return $session->invalidate(); }
            
            throw $e;
        } //try-catch ends
    } //Function ends


    /**
     * Attempt MFA based Authentication
     */
    public function attemptMFA(array $challenge = [], Authenticatable $user, bool $remember=false) {
        try {
            $claim = null;

            $response = $this->attemptBaseMFA($challenge, $user, $remember);
            //Result of type AWS Result
            if (!empty($response)) {

                //Handle the response as Aws Cognito Claim
                if ($response instanceof AwsCognitoClaim) {
                    $claim = $response;

                    //Get Session and store details
                    $session = $this->getSession();
                    $session->forget($challenge['session']);
                    $session->put('claim', json_decode(json_encode($claim), true));

                    //Login user into the session
                    $this->login($user, $remember);

                    //Fire successful attempt
                    $this->fireValidatedEvent($user);
                    $this->fireAuthenticatedEvent($user);
                    
                    return true;                    
                } //End if

                //Handle if the object is a Aws Cognito Result
                if ($response instanceof AwsResult) {
                    //Check in case of any challenge
                    if (isset($response['ChallengeName'])) {

                    } else {

                    } //End if
                } //End if
            } //End if
        } catch(Exception $e) {
            throw $e;
        } //Try-catch ends
    } //Function ends

} //Class ends
