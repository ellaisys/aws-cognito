<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

use Ellaisys\Cognito\AwsCognito;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoTokenException;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

abstract class BaseMiddleware //extends Middleware
{
    /**
     * The Cognito Authenticator.
     *
     * @var \Ellaisys\Cognito\AwsCognito
     */
    protected $cognito;

    /**
     * Create a new BaseMiddleware instance.
     *
     * @param  \Ellaisys\Cognito\AwsCognito  $cognito
     *
     * @return void
     */
    public function __construct(AwsCognito $cognito)
    {
        $this->cognito = $cognito;
    }

    /**
     * Check the request for the presence of a token.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @throws \Ellaisys\Cognito\Exceptions\NoTokenException
     *
     * @return void
     */
    public function checkForToken(Request $request)
    {
        if (! $this->cognito->parser()->setRequest($request)->hasToken()) {
            throw new NoTokenException();
        } //End if
    } //Function ends

    /**
     * Check the request for the presence of a cognito user.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     *
     * @return void
     */
    public function checkForUser(Request $request)
    {
        $cognitoUser = $this->cognito->setRequest($request)->parseToken()->authenticate();
        if (empty($cognitoUser)) {
            throw new UnauthorizedHttpException('aws-cognito', 'User not found');
        } //End if
    } //Function ends

    /**
     * Attempt to authenticate a user via the token in the request.
     *
     * @param  \Illuminate\Http\Request  $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException
     *
     * @return void
     */
    public function authenticate(Request $request, string $guard)
    {
        try {
            // Validate the token
            $this->checkForToken($request);

            // Validate Cognito User
            $this->checkForUser($request);
            
            switch ($guard) {
                case 'web':
                    $user = $request->user();
                    if (empty($user)) {
                        throw new UnauthorizedHttpException('aws-cognito', 'User not found');
                    } //End if
                    break;
                
                case 'api':
                default:
                    break;
            } //Switch ends
        } catch (Exception $e) {
            Log::error('BaseMiddleware:authenticate:Exception');
            if (($guard=='web') && ($e instanceof NoTokenException || $e instanceof InvalidTokenException)) {
                throw new AuthenticationException($e->getMessage());
            } //End if
            throw $e;
        } //Try-catch ends
    } //Function ends

    /**
     * Set the authentication header.
     *
     * @param  \Illuminate\Http\Response|\Illuminate\Http\JsonResponse  $response
     * @param  string|null  $token
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    protected function setAuthenticationHeader($response, $token = null)
    {
        $token = $token ?: $this->cognito->refresh();
        $response->headers->set('Authorization', 'Bearer '.$token);

        return $response;
    } //Function ends

} //Class ends
