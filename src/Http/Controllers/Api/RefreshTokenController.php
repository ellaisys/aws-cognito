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
use Ellaisys\Cognito\Auth\RefreshToken;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;


class RefreshTokenController extends Controller
{
    use RefreshToken;


    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        //Mandate authentication for all the API's of this controller
        $this->middleware('aws-cognito:api');

        parent::__construct();
    }


    /**
     * Action to refresh the token
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function actionRefreshToken(Request $request)
    {
        try {
            //Call the refresh token API
            $response = $this->refresh($request);

            //Return the response
            return $this->response->success($response);

        } catch (Exception $e) {
            return $e;
        }
    } //Function ends

} //Class ends
