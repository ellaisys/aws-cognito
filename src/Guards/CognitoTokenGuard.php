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

use Exception;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserModelException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;

class CognitoTokenGuard extends TokenGuard
{

    /**
     * @var  \string  Username key
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
     * @var \array
     */
    protected $storage;


    /**
     * CognitoTokenGuard constructor.
     * 
     * @param $callback
     * @param AwsCognitoClient $client
     * @param Request $request
     * @param UserProvider $provider
     */
    public function __construct(
        AwsCognito $facade,
        AwsCognitoClient $client, 
        Request $request, 
        UserProvider $provider = null,
        string $keyUsername = 'email'
    ) {
        $this->cognito = $facade;
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

            //Create token object
            $store = [];
            $store['token'] = $result['AuthenticationResult']['AccessToken'];
            $store['value'] = $result['AuthenticationResult'];
            $store['value']['username'] = $credentials[$this->keyUsername];

            //Set storage
            $this->storage = $store;

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
                $token = $this->login($user);
                return $token;
            } //End if

            //$this->fireFailedEvent($user, $credentials);

            return false;
        } catch (NoLocalUserException $e) {
            Log::error('CognitoTokenGuard:NoLocalUserException');
            throw new NoLocalUserException();
        } catch (AwsCognitoException $e) {
            Log::error('CognitoTokenGuard:AwsCognitoException'. $e->getMessage());
            throw new AwsCognitoException();
        } catch (Exception $e) {
            Log::error($e->getMessage());
            return false;
        } //Try-catch ends
    } //Function ends


    /**
     * Create a token for a user.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     *
     * @return string
     */
    private function login($user)
    {
        $token = $this->storage['token'];
        $this->setToken($token);
        $this->setUser($user);

        return $token;
    } //Fucntion ends


    /**
     * Set the token.
     *
     * @param  \Ellaisys\Cognito\AwsCognitoToken|string  $token
     *
     * @return $this
     */
    public function setToken($token)
    {
        $this->cognito->setToken($token);

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
        $this->cognito->unsetToken();
    }


    /**
     * Invalidate the token.
     *
     * @param  bool  $forceForever
     *
     * @return \Tymon\JWTAuth\JWT
     */
    public function invalidate($forceForever = false)
    {
        return $this->requireToken()->invalidate($forceForever);
    }

} //Class ends