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

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\AwsCognitoUserPool;
use Ellaisys\Cognito\Enums\CognitoUserStatusTypes;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\InvalidUserFieldException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

trait ResetsPasswords
{
    use BaseAuthTrait;

    /**
     * private variable for password policy
     */
    private $passwordPolicy = null;

    /**
     * Passed params
     */
    private $paramToken = 'token';
    private $paramCode = 'code';
    private $paramUsername = 'email';
    private $paramPassword = 'password';

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request|Illuminate\Support\Collection  $request
     * @param  string  $paramUsername (optional)
     * @param  string  $paramToken (optional)
     * @param  string  $paramPassword (optional)
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function reset(Request $request,
        string $paramUsername='email', string $paramToken='token',
        string $paramPassword='password'): mixed
    {
        $response = '';
        try {
            //Assign params
            $this->paramUsername = $paramUsername;
            $this->paramToken = $paramToken;
            $this->paramPassword = $paramPassword;

            //Get the password policy
            $this->passwordPolicy = app()->make(AwsCognitoUserPool::class)->getPasswordPolicy(true);

            //Validate request
            $validator = Validator::make($request->all(), $this->rules(), [
                'regex' => $this->passwordPolicy['message'],
            ]);
            if ($validator->fails()) {
                throw new ValidationException($validator);
            } //End if

            //Create AWS Cognito Client
            $client = app()->make(AwsCognitoClient::class);

            //Get User Data
            $user = $client->adminGetUser($request[$paramUsername]);
            if ($user) {
                //Check user status and change password
                $userStatus = CognitoUserStatusTypes::from($user['UserStatus']);

                //Check if user is confirmed or reset required
                if (($userStatus == CognitoUserStatusTypes::CONFIRMED) ||
                    ($userStatus == CognitoUserStatusTypes::RESET_REQUIRED)) {
                    $response = $client->resetPassword(
                            $request[$paramToken],
                            $request[$paramUsername],
                            $request[$paramPassword]
                        );
                } else {
                    throw new HttpException(400, 'User status is not valid for password reset.');
                } //End if
            } else {
                throw new HttpException(400, 'User not found.');
            } //End if

            //Return response
            if ($this->isControllerAction) {
                $returnValue = $response;
            } elseif ($this->getIsJsonResponse($request)) {
                $returnValue = $this->response->success($response);
            } else {
                $returnValue = redirect()
                    ->route($this->redirectPath())
                    ->with('status', 'Password reset successfully.')
                    ->with('data', $response);
            } //Return response

        } catch(Exception $exception) {
            Log::error('ResetsPasswords:reset:Exception');
            throw $exception;
        } //Try-Catch ends

        return $returnValue;
    } //Function ends

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(Request $request, string $token = null)
    {
        return view('cognito.form.password.reset')->with(
            [
                'email' => $request->email ?? '',
                'token' => $token
            ]
        );
    } //Function ends

    /**
     * Get the password reset validation rules.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            $this->paramToken       => 'required_without:'.$this->paramCode,
            $this->paramCode        => 'required_without:'.$this->paramToken,
            $this->paramUsername    => 'required|email',
            $this->paramPassword    => 'required|confirmed|regex:'.$this->passwordPolicy['regex'],
        ];
    } //Function ends

} //Trait ends
