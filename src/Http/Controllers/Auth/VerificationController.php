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

use Ellaisys\Cognito\Auth\VerifiesEmails;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\Events\Auth\PreRegistrationEvent;
use Ellaisys\Cognito\Events\Auth\PostRegistrationEvent;

use Exception;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    use VerifiesEmails;

    /**
     * Client metadata to be sent to AWS Cognito
     *
     * @var array|null
     */
    protected $clientMetadata = null;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');

        //Set flag to indicate action called from controller
        $this->setIsControllerAction(false);

        parent::__construct();

        // $this->middleware('signed')->only('verify');
    } //Function ends

} //Class ends
