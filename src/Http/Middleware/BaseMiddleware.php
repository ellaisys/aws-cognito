<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

use Ellaisys\Cognito\AwsCognito;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\TokenInvalidException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

abstract class BaseMiddleware extends Middleware
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
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return void
     */
    public function checkForToken(Request $request)
    {
        if (! $this->cognito->parser()->setRequest($request)->hasToken()) {
            throw new UnauthorizedHttpException('aws-cognito', 'Token not provided');
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
    public function authenticate(Request $request)
    {
        try {
            $this->checkForToken($request);

            if (! $this->cognito->parseToken()->authenticate()) {
                throw new UnauthorizedHttpException('aws-cognito', 'User not found');
            } //End if
        } catch (TokenInvalidException $e) {
            throw new UnauthorizedHttpException('aws-cognito', 'Token is invalid.');
        } catch (AwsCognitoException $e) {
            throw new UnauthorizedHttpException('aws-cognito', $e->getMessage(), $e, $e->getCode());
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