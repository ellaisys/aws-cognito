<?php

namespace Ellaisys\Cognito\Exceptions;

use Throwable;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

class TokenInvalidException extends Exception
{

    /**
     * Report the exception.
     *
     * @return void
     */
    public function report()
    {
        throw new AuthenticationException();
        // throw new UnauthorizedHttpException(401, "Invalid Authentication Token");
    }


    /**
     * Render the exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Throwable $exception)
    {
        return parent::render($request, $exception);
    }
    
} //Class ends