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

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;


class UserController extends Controller
{

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
    public function actionGetRemoteUser(Request $request)
    {
        try {
            //Get the existing user from token
            $user =  auth()->guard('api')->user();

            //Call the details of the user from AWS Cognito
            $response = auth()->guard()->getRemoteUserData($user['email']);
            if (isset($response['@metadata']['statusCode']) && $response['@metadata']['statusCode']==200) {
                $data = $response->toArray();
                unset($data['@metadata']);

                return $this->response->success($data);
            } else {
                throw new HttpException(400, 'Error fetching user details from AWS Cognito.');
            } //End if

        } catch (Exception $e) {
            return $e;
        }
    } //Function ends

} //Class ends
