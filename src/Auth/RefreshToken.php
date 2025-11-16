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
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

trait RefreshToken
{
    /**
     * Passed params
     */
    private $paramRefreshToken = 'refresh_token';

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

            //Validate request
            $validator = Validator::make($request->all(), $this->rules());

            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Convert request to collection
            if ($request instanceof Request) {
                $request = collect($request->all());
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get Authenticated user
            $authUser  = Auth::guard($guard)->user();

            //Get User Data
            $user = $client->getUser($authUser[$paramUsername]);

            //Use username from AWS to refresh token, not email from login!
            if (!empty($user['Username'])) {
                $response = $client->refreshToken($user['Username'], $request[$paramRefreshToken]);
                if (empty($response) || empty($response['AuthenticationResult'])) {
                    throw new HttpException(400);
                } //End if

                //Authenticate User
                $claim = new AwsCognitoClaim($response, $authUser, 'email');
                if ($claim && $claim instanceof AwsCognitoClaim) {
                    //Store the token
                    $cognito = app()->make('ellaisys.aws.cognito');
                    if (empty($cognito)) {
                        throw new HttpException(400, 'ERROR_COGNITO_TOKEN_STORE');
                    } //End if
                    $cognito->setClaim($claim)->storeToken();

                    //Return the response object
                    return $claim->getData();
                } else {
                    return false;
                } //End if
            } else {
                throw new HttpException(400, 'ERROR_COGNITO_USER_NOT_FOUND');
            } //End if
        } catch(Exception $e) {
            if ($e instanceof CognitoIdentityProviderException) {
                throw AwsCognitoException::create($e);
            } //End if
            throw $e;
        } //Try-catch ends
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
