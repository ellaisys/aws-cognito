<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <support@ellaisys.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Http\Controllers\Api;

use Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request;

use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\Auth\RegistersUsers;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;


class RegisterController extends Controller
{
    use RegistersUsers;


    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        //All the API's of this controller are public
        $this->middleware('guest');

        parent::__construct();
    }
    

    /**
     * Action to register the a new user
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function actionRegister(Request $request)
    {
        try {
            //Create credentials object
            $collection = collect($request->all());

            //Validate request and get registration data
            $user = $this->register($request);
            if ($user) {
                return $this->response->success($user);
            } //End if
        } catch (Exception $e) {
            throw $e;
        } //End try-catch
    } //Function ends

} //Class ends
