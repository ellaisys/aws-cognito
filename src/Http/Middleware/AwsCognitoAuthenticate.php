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

use Illuminate\Http\Request;
use Illuminate\Auth\Middleware\Authenticate as Middleware;

use Illuminate\Support\Facades\Log;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoTokenException;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AwsCognitoAuthenticate extends BaseMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, \Closure $next)
    {
        $guard='';
        $middlewares=[];

        try {
            $routeMiddleware = $request->route()->middleware();
            if (empty($routeMiddleware) || (count($routeMiddleware)<1)) {
                throw new InvalidTokenException();
            } else {
                // Set the guard and middlewares
                $guard = $routeMiddleware[0];

                // Middllewares
                $middlewares = $routeMiddleware;
            } //End if

            //Authenticate the request
            if (!empty($middlewares) && in_array('aws-cognito', $middlewares)) {
                $this->authenticate($request, $guard);
            } //End if

            return $next($request);
        } catch (Exception $e) {
            Log::error('AwsCognitoAuthenticate:handle:Exception');
            throw $e;
        } //Try-catch ends
    } //Function ends

} //Class ends
