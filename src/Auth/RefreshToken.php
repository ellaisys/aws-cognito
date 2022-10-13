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

use App\Models\User;
use Ellaisys\Cognito\AwsCognitoClaim;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\AwsCognitoClient;

trait RefreshToken
{
    /**
     * Generate a new token.
     *
     * @param  \Illuminate\Http\Request|Illuminate\Support\Collection  $request
     * @param  string  $paramUsername (optional)
     * @param  string  $paramRefreshToken (optional)
     *
     * @return mixed
     */
    public function refresh($request, string $paramUsername='email', string $paramRefreshToken='refresh_token')
    {
        if ($request instanceof Request) {
            //Validate request
            $validator = Validator::make($request->all(), $this->rules());

            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            $request = collect($request->all());
        } //End if

        //Create AWS Cognito Client
        $client = app()->make(AwsCognitoClient::class);

        //Get User Data
        $user = $client->getUser($request[$paramUsername]);

        //Use username from AWS to refresh token, not email from login!
        if (!empty($user['Username'])) {
            $response = $client->refreshToken($user['Username'], $request[$paramRefreshToken]);

            //Authenticate User
            $user = User::where('email', $request['email'])->first();
            $claim = new AwsCognitoClaim($response, $user, 'email');
            $this->cognito->setClaim($claim)->storeToken();
            return $claim;
        } else {
            $response = response()->json(['error' => 'cognito.validation.invalid_username'], 400);
        } //End if

        return $response;
    } //Function ends


    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'email'    => 'required|email',
            'refresh_token'    => 'required'
        ];
    } //Function ends

} //Trait ends
