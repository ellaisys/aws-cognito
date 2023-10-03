<?php

namespace Ellaisys\Cognito\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class BaseCognitoController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
