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
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;
use Ellaisys\Cognito\Auth\ConfirmsPasswords; //Added for AWS Cognito

use Exception;

class ConfirmPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Confirm Password Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password confirmations and
    | uses a simple trait to include the behavior. You're free to explore
    | this trait and override any functions that require customization.
    |
    */

    use ConfirmsPasswords;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        //Mandate authentication for all the API's of this controller
        $this->middleware('aws-cognito');

        //Set flag to indicate action called from controller
        $this->setIsControllerAction(false);

        parent::__construct();
    }

} //Class ends
