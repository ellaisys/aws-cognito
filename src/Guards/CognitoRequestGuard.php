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
use Illuminate\Auth\RequestGuard;
use Illuminate\Contracts\Auth\UserProvider;

use Ellaisys\Cognito\AwsCognitoClient;

use Exception;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserModelException;

class CognitoRequestGuard extends RequestGuard
{

    /**
     * @var AwsCognitoClient
     */
    protected $client;


    /**
     * @var array
     */
    protected $storage;


    /**
     * CognitoRequestGuard constructor.
     * 
     * @param $callback
     * @param AwsCognitoClient $client
     * @param Request $request
     * @param UserProvider $provider
     */
    public function __construct(
        $callback,
        AwsCognitoClient $client, 
        Request $request, 
        UserProvider $provider = null
    ) {
        $this->client = $client;
        parent::__construct($callback, $request, $provider);
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
        $result = $this->client->authenticate($credentials['username'], $credentials['password']);

        if (!empty($result) && $result instanceof AwsResult) {
            if ($user instanceof Authenticatable) {
                //Create token object
                $store = [];
                $store['token'] = $result['AuthenticationResult']['AccessToken'];
                $store['value'] = $result['AuthenticationResult'];
                $store['value']['username'] = $credentials['username'];

                //Save store data to storage

                //Set storage
                $this->storage = $store;

                return true;
            } else {
                throw new NoLocalUserException();
            } //End if
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
            $this->fireAttemptEvent($credentials, $remember);

            $this->lastAttempted = $user = $this->provider->retrieveByCredentials($credentials);

            if ($this->hasValidCredentials($user, $credentials)) {
                $this->login($user, $remember);
                return true;
            } //End if

            $this->fireFailedEvent($user, $credentials);

            return false;
        } catch (NoLocalUserException $e) {
                
        } catch (Exception $e) {
            return false;
        } //Try-catch ends
    } //Function ends

} //Class ends