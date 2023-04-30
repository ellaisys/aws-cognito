<?php

namespace Ellaisys\Cognito\Traits;

use Config;
use Carbon\Carbon;

use Illuminate\Support\Facades\Log;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * AWS Cognito Client for MFA Actions
 */
trait AwsCognitoClientMFAAction
{

} //Trait ends