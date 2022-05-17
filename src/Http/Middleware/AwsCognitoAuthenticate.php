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
    public function handle(Request $request, Closure $next, $guards=null)
    {
        $guard='';
        $middleware='';
        $countRouteMiddleware=0;

        try {
            $routeMiddleware = $request->route()->middleware();
            if (empty($routeMiddleware) || (($countRouteMiddleware=count($routeMiddleware))<1)) {
                return response()->json(['error' => 'UNAUTHORIZED_REQUEST', 'exception' => null], 401);
            } else {
                ($countRouteMiddleware>0)?($guard = $routeMiddleware[0]):null;
                ($countRouteMiddleware>1)?($middleware = $routeMiddleware[1]):null;
            } //End if

            //Authenticate the request
            $this->authenticate($request);

            return $next($request);
        } catch (Exception $e) {
            if ($e instanceof NoTokenException) {
                return response()->json(['error' => 'UNAUTHORIZED_REQUEST', 'exception' => 'NoTokenException'], 401);             
            } //End if

            if ($e instanceof InvalidTokenException) {
                return response()->json(['error' => 'UNAUTHORIZED_REQUEST', 'exception' => 'InvalidTokenException'], 401);
            } //End if

            //Raise error in case of generic error
            if ($guard=='web') {
                return redirect('/');
            } else {
                return response()->json(['error' => $e->getMessage()], 401);
            } //End if
        } //Try-catch ends
    } //Function ends

} //Class ends