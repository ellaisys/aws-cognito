<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Http\Controllers\Auth;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;

use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\Auth\DeviceActions;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator;

use Exception;

class DeviceController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Device Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles device operations for the application.
    | that used a session or api call. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use DeviceActions;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        //Mandate authentication for all the API's of this controller
        $this->middleware('aws-cognito')->except([
                'challenge'
            ]);

        //Set flag to indicate action called from controller
        $this->setIsControllerAction(false);

        parent::__construct();
    }

} //Class ends
