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
use Ellaisys\Cognito\Auth\WebAuthPasskey;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator;

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
        //Mandate authentication for all the API's of this controller
        $this->middleware('aws-cognito')->except([
                'challenge'
            ]);

        //Set flag to indicate action called from controller
        $this->setIsControllerAction(true);

        parent::__construct();
    }

    /**
     * Get the post register / login redirect path.
     *
     * @return string
     */
    public function redirectPath()
    {
        //Check if property exists and not null
        if (property_exists($this, 'redirectTo') && !is_null($this->redirectTo)) {
            return $this->redirectTo;
        } //End if

        return config('cognito.routes.web.login_page', 'cognito.form.login');
    } //Function ends

} //Class ends
