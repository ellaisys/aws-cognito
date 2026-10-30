<?php

namespace Ellaisys\Cognito\Contracts;

interface ExceptionContract
{
    /**
     * Handle an exception.
     *
     * @param \Throwable|\Exception $exception
     * @return \Illuminate\Http\Response
     */
    public function handle($exception);
}
