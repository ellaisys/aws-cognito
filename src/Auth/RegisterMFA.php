<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Auth;

use Auth;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\AwsCognito;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoClaim;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

trait RegisterMFA
{

    /**
     * Activate the MFA for the authenticated user
     *
     * @param  string  $guard (optional)
     *
     * @return mixed
     */
    public function activateMFA(string $guard='web')
    {
        return Auth::guard($guard)->associateSoftwareTokenMFA();
    } //Function ends


    /**
     * Verify the MFA for the authenticated user
     *
     * @param  string  $guard (optional)
     *
     * @return mixed
     */
    public function verifyMFA(string $guard='web', string $userCode, string $deviceName='my device')
    {
        $response = Auth::guard($guard)->verifySoftwareTokenMFA($userCode, $deviceName);
        if (!empty($response) && ($response['Status']=='SUCCESS')) {
            return $this->toggleMFA($guard, true);
        } //End if
    } //Function ends


    /**
     * Deactivate the MFA for the authenticated user
     *
     * @param  string  $guard (optional)
     *
     * @return mixed
     */
    public function deactivateMFA(string $guard='web')
    {
        return $this->toggleMFA($guard, false);
    } //Function ends


    /**
     * Toggle the MFA for the authenticated user
     * 
     * @param  string  $guard
     * @param  bool    $isEnable (optional)
     *
     * @return array
     */
    private function toggleMFA(string $guard, bool $isEnable=false)
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get Authenticated user
            $authUser = Auth::guard($guard)->user();
            if (empty($authUser)) { throw new InvalidUserException(); }

            //Token Object
            $objToken = Auth::guard($guard)->cognito()->getToken();
            if (empty($authUser)) { throw new HttpException(400, 'EXCEPTION_INVALID_TOKEN'); }

            //Access Token
            $accessToken = $objToken;
            
            //Use username from AWS to refresh token, not email from login!
            if (!empty($accessToken)) {
                $response = $client->setUserMFAPreference($accessToken, $isEnable);
                if (empty($response)) {
                    throw new HttpException(400);
                } //End if
            } else {
                throw new HttpException(400, 'EXCEPTION_INVALID_USERNAME_OR_TOKEN');
            } //End if
        } catch(Exception $e) {
            if ($e instanceof CognitoIdentityProviderException) {
                throw new HttpException(400, $e->getAwsErrorMessage(), $e);
            } //End if
            throw $e;
        } //Try-catch ends
    } //Function ends


    /**
     * Enable the MFA for the mentioned user
     *
     * @param  string  $guard (optional)
     * @param  string  $username
     *
     * @return mixed
     */
    public function enableMFA(string $guard='web', string $username)
    {
        return $this->toggleAdminMFA($guard, $username, true);
    } //Function ends


    /**
     * Disable the MFA for the mentioned user
     *
     * @param  string  $guard (optional)
     * @param  string  $username
     *
     * @return mixed
     */
    public function disableMFA(string $guard='web', string $username)
    {
        return $this->toggleAdminMFA($guard, $username, false);
    } //Function ends


    /**
     * Toggle the MFA by the admin user
     * 
     * @param  string  $guard
     * @param  string  $username
     * @param  bool    $isEnable (optional)
     *
     * @return array
     */
    private function toggleAdminMFA(string $guard, string $username, bool $isEnable=false)
    {
        try {
            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get Authenticated user
            $authUser = Auth::guard($guard)->user();
            if (empty($authUser)) { throw new InvalidUserException(); }
           
            //Use username for the MFA configurations
            return $client->setUserMFAPreferenceByAdmin($username, $isEnable);
        } catch(Exception $e) {
            if ($e instanceof CognitoIdentityProviderException) {
                throw new AwsCognitoException($e->getAwsErrorMessage(), $e->getAwsErrorCode(), $e);
            } //End if

            throw $e;
        } //Try-catch ends
    } //Function ends

} //Trait ends
