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
use Illuminate\Http\Request;
use Illuminate\Auth\TokenGuard;
use Illuminate\Support\Facades\Log;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

use Ellaisys\Cognito\AwsCognito;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoClaim;

use Exception;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserModelException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
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

            //Create claim token
            $this->claim = new AwsCognitoClaim($result, $user, $credentials[$this->keyUsername]);

            if ($user instanceof Authenticatable) {
                return true;
            } else {
                throw new NoLocalUserException();
            } //End if
        } else {
            throw new AwsCognitoException();
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
            //$this->fireAttemptEvent($credentials, $remember);

            $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

            if ($this->hasValidCredentials($user, $credentials)) {
                return $this->login($user);
            } //End if

            //$this->fireFailedEvent($user, $credentials);

            return false;
        } catch (NoLocalUserException $e) {
            Log::error('CognitoTokenGuard:attempt:NoLocalUserException:');
            throw new NoLocalUserException();
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
            throw new AwsCognitoException();
        } catch (Exception $e) {
            Log::error('CognitoTokenGuard:attempt:Exception:'.$e->getMessage());
            return false;
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
            //Set Token
            $this->setToken();

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
     * @return \Ellaisys\Cognito\AwsCognito
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