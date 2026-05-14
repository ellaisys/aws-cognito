<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
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
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

trait RefreshToken
{
    /**
     * Passed params
     */
    private $paramRefreshToken = 'refresh_token';
    private $paramUsername = 'email';

    /**
     * Generate a new token.
     *
     * @param  \Illuminate\Http\Request|Illuminate\Support\Collection  $request
     * @param  string  $paramUsername (optional)
     * @param  string  $paramRefreshToken (optional)
     *
     * @return mixed
     */
    public function refresh(Request $request,
        string $guard = 'api',
        string $paramUsername='email',
        string $paramRefreshToken='refresh_token')
    {
        try {
            //Assign params
            $this->paramRefreshToken = $paramRefreshToken;
            $this->paramUsername = $paramUsername;
            
            //Validate request
            $this->validateRefreshRequest($request);

            //Process token refresh
            return $this->processTokenRefresh($request, $guard);
        } catch(Exception $e) {
            if ($e instanceof CognitoIdentityProviderException) {
                throw AwsCognitoException::create($e);
            } //End if
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Validate the refresh token request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return void
     */
    private function validateRefreshRequest(Request $request)
    {
        try{
            $validator = Validator::make($request->all(), $this->rules());

            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if
        } catch (ValidationException $e) {
            throw $e;
        } catch (Exception $e) {
            throw new HttpException(400, 'ERROR_VALIDATION');
        } //Try-catch ends
    } //Function ends

    /**
     * Process the token refresh.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @return mixed
     */
    private function processTokenRefresh(Request $request, string $guard): mixed
    {
        //Convert request to collection
        $payload = collect($request->all());

        //Create AWS Cognito Client
        $client = app()->make(AwsCognitoClient::class);

        //Get Authenticated user
        $authUser  = Auth::guard($guard)->user();

        //Get User Data
        $user = $client->adminGetUser($authUser[$this->paramUsername]);

        //Use username from AWS to refresh token, not email from login!
        if (!empty($user['Username'])) {
            $response = $client->refreshToken($user['Username'], $payload[$this->paramRefreshToken]);
            if (empty($response) || empty($response['AuthenticationResult'])) {
                throw new HttpException(400);
            } //End if

            //Authenticate User
            $claim = new AwsCognitoClaim($response, $authUser, $this->paramUsername);
            if (!($claim instanceof AwsCognitoClaim)) {
                return false;
            } //End if

            //Store the token
            $cognito = app()->make('ellaisys.aws.cognito');
            if (empty($cognito)) {
                throw new HttpException(400, 'ERROR_COGNITO_TOKEN_STORE');
            } //End if
            $cognito->setClaim($claim)->storeToken();

            //Return the response object
            return $claim->getData();
        } else {
            throw new HttpException(400, 'ERROR_COGNITO_USER_NOT_FOUND');
        } //End if
    } //Function ends

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            $this->paramRefreshToken => 'required'
        ];
    } //Function ends

} //Trait ends
