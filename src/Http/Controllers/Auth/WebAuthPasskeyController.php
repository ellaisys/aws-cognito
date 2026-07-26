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

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;

use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\Auth\WebAuthPasskey;

use Exception;

class WebAuthPasskeyController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Web Auth Passkey Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles Web Auth Passkey operations for the application.
    | that used a session or api call. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use WebAuthPasskey;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        /*
         * Mandate authentication for all the APIs of this controller
         * except the challenge method.
         */
        $this->middleware('aws-cognito')->except([
                'challenge'
            ]);

        // Controller action flag
        $this->setIsControllerAction(false);

        parent::__construct();
    }

} //Class ends
