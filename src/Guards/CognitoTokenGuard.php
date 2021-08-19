<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sunnydesign\Cognito\Guards;

use Aws\Result as AwsResult;
use Illuminate\Http\Request;
use Illuminate\Auth\TokenGuard;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

use Sunnydesign\Cognito\AwsCognito;
use Sunnydesign\Cognito\AwsCognitoClient;
use Sunnydesign\Cognito\AwsCognitoClaim;

use Exception;
use Sunnydesign\Cognito\Exceptions\NoLocalUserException;
use Sunnydesign\Cognito\Exceptions\InvalidUserModelException;
use Sunnydesign\Cognito\Exceptions\AwsCognitoException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class CognitoTokenGuard extends TokenGuard
{

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
     * @var \Sunnydesign\Cognito\AwsCognito
     */
    protected $cognito;


    /**
     * The AwsCognito Claim token
     * 
     * @var \Sunnydesign\Cognito\AwsCognitoClaim|null
     */
    protected $claim;


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
        UserProvider $provider = null,
        string $keyUsername = 'email'
    ) {
        $this->cognito = $cognito;
        $this->client = $client;
        $this->keyUsername = $keyUsername;

        parent::__construct($provider, $request);
    }


    /**
     * @param mixed $user
     * @param array $credentials
     * @return bool
     * @throws InvalidUserModelException
     */
    protected function hasValidCredentials($user, $credentials)
    {
        /** @var Result $response */
        $result = $this->client->authenticate($credentials[$this->keyUsername], $credentials['password']);
       
        if (!empty($result) && $result instanceof AwsResult) {

            if (isset($result['ChallengeName']) && 
                in_array($result['ChallengeName'], config('cognito.forced_challenge_names'))) 
            {
                //Check for forced action on challenge status
                if (config('cognito.force_password_change_api')) {
                    $this->claim = [
                        'session_token' => $result['Session'],
                        'username' => $credentials[$this->keyUsername],
                        'status' => $result['ChallengeName']
                    ];
                } else {
                    if (config('cognito.force_password_auto_update_api')) {
                        //Force set password same as authenticated with challenge state
                        $this->client->confirmPassword($credentials[$this->keyUsername], $credentials['password'], $result['Session']);

                        //Get the result object again
                        $result = $this->client->authenticate($credentials[$this->keyUsername], $credentials['password']);
                        if (empty($result)) {
                            return false;
                        } //End if
                    } else {
                        $this->claim = null;
                    } //End if
                } //End if
            } //End if

            //Create Claim for confirmed users
            if (!isset($result['ChallengeName'])) {
                //Create claim token
                $this->claim = new AwsCognitoClaim($result, $user, $credentials[$this->keyUsername]);
            } //End if     

            return ($this->claim)?true:false;
        } else {
            return false;
        } //End if

        return false;
    } //Function ends


    /**
     * Attempt to authenticate a user using the given credentials.
     *
     * @param  array  $credentials
     * @param  bool   $remember
     * @throws
     * @return bool
     */
    public function attempt(array $credentials = [], $remember = false)
    {
        try {
            $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

            //Check if the user exists in local data store
            if (!($user instanceof Authenticatable)) {
                throw new NoLocalUserException();
            } //End if

            if ($this->hasValidCredentials($user, $credentials)) {
                return $this->login($user);
            } //End if

            return false;
        } catch (NoLocalUserException $e) {
            Log::error('CognitoTokenGuard:attempt:NoLocalUserException:');
            throw $e;
        } catch (CognitoIdentityProviderException $e) {
            Log::error('CognitoTokenGuard:attempt:CognitoIdentityProviderException:'.$e->getAwsErrorCode());

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

                return response()->json(['error' => $errorCode, 'message' => $e->getAwsErrorCode() ], 400);
            } //End if

            return $e->getAwsErrorCode();
        } catch (AwsCognitoException $e) {
            Log::error('CognitoTokenGuard:attempt:AwsCognitoException:'. $e->getMessage());
            throw $e;
        } catch (Exception $e) {
            Log::error('CognitoTokenGuard:attempt:Exception:'.$e->getMessage());
            throw $e;
        } //Try-catch ends
    } //Function ends


    /**
     * Create a token for a user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     *
     * @return claim
     */
    private function login($user)
    {
        if (!empty($this->claim)) {

            //Save the claim if it matches the Cognito Claim
            if ($this->claim instanceof AwsCognitoClaim) {

                //Set Token
                $this->setToken();
            } //End if

            //Set user
            $this->setUser($user);
        } //End if

        return $this->claim;
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
     * @return \Sunnydesign\Cognito\AwsCognito
     */
    public function invalidate($forceForever = false)
    {
        return $this->cognito->unsetToken($forceForever);
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

} //Class ends