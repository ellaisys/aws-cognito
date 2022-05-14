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

use Ellaisys\Cognito\AwsCognitoClient;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Ellaisys\Cognito\Exceptions\InvalidUserModelException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class CognitoSessionGuard extends SessionGuard implements StatefulGuard
{
    /**
     * @var AwsCognitoClient
     */
    protected $client;


    /**
     * @var Authentication Challenge
     */
    protected $challengeName;


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
        AwsCognitoClient $client,
        UserProvider $provider,
        Session $session,
        ?Request $request = null
    ) {
        $this->client = $client;
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
        /** @var Result $response */
        $result = $this->client->authenticate($credentials['email'], $credentials['password']);

        if (!empty($result) && $result instanceof AwsResult) {

            if (isset($result['ChallengeName']) && 
                in_array($result['ChallengeName'], config('cognito.forced_challenge_names'))) 
            {
                $this->challengeName = $result['ChallengeName'];
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
            if (!($user instanceof Authenticatable)) {
                throw new NoLocalUserException();
            } //End if

            //Authenticate with cognito
            if ($this->hasValidCredentials($user, $credentials)) {
                $this->login($user, $remember);

                //Fire successful attempt
                $this->fireAuthenticatedEvent($user);

                if ((!empty($this->challengeName)) && config('cognito.force_password_change_web')) {
                    switch ($this->challengeName) {
                        case AwsCognitoClient::NEW_PASSWORD_CHALLENGE:
                        case AwsCognitoClient::RESET_REQUIRED_PASSWORD:
                            return redirect(route(config('cognito.force_redirect_route_name')))
                                ->with('success', true)
                                ->with('force', true)
                                ->with('messaage', $this->challengeName);
                            break;
                        
                        default:
                            return true;
                            break;
                    } //End switch
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
            $this->fireFailedEvent($user, $credentials);

            return false;
        } //Try-catch ends
    } //Function ends

} //Class ends