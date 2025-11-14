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
use Ellaisys\Cognito\Auth\RefreshToken;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;


class RefreshTokenController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Refresh Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles refreshng the users token for the application
    | that used a session or api call. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use RefreshToken;

    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        //Mandate authentication for all the API's of this controller
        $this->middleware('aws-cognito');

        parent::__construct();
    }

    /**
     * Action to revalidate the token
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function revalidate(Request $request)
    {
        try {
            //Initialize parameters
            $returnValue = null;
            $guard = 'web';
            $isJsonResponse = false;

            //Check if request is json
            if ($this->isJson($request)) {
                $isJsonResponse = true;
                $guard = 'api';
            } //End if

            //Call the refresh token API
            $response = $this->refresh($request, $guard);

            //Return the response
            if ($isJsonResponse) {
                $returnValue = $this->response->success($response);
            }
            return $returnValue;
        } catch (Exception $e) {
            Log::error('RefreshTokenController:revalidate:Exception');
            return $e;
        }
    } //Function ends

} //Class ends
