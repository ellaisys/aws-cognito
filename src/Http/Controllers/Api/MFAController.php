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
use Illuminate\Http\Request;

use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\Auth\AuthenticatesUsers;
use Ellaisys\Cognito\Auth\RegisterMFA;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;

use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Support\Facades\Validator;

use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MFAController extends Controller
{
    use AuthenticatesUsers;
    use RegisterMFA;


    /**
     * Constructor.
     *
     */
    public function __construct()
    {
        parent::__construct();
    }


	/**
	 * Action to activate MFA
	 */
    public function actionApiActivateMFA()
    {
		try
		{
            return $this->activateMFA('api');
        } catch(Exception $e) {
			$message = 'Error activating the MFA.';
			if ($e instanceof ValidationException) {
                $message = $e->errors();
            } else if ($e instanceof CognitoIdentityProviderException) {
				$message = $e->getAwsErrorMessage();
			} else {
                //Do nothing
            } //End if

			throw $e;
        } //Try-catch ends
    } //Function ends


	/**
	 * Action to deactivate MFA for the user
	 */
    public function actionApiDeactivateMFA()
    {
		try
		{
            return $this->deactivateMFA('api');
        } catch(Exception $e) {
			$message = 'Error activating the MFA.';
			if ($e instanceof ValidationException) {
                $message = $e->errors();
            } else if ($e instanceof CognitoIdentityProviderException) {
				$message = $e->getAwsErrorMessage();
			} else {
                //Do nothing
            } //End if

			throw $e;
        } //Try-catch ends
    } //Function ends


	/**
	 * Action to enable MFA for the user
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 */
    public function actionApiEnableMFA(Request $request, string $paramUsername='username')
    {
		try
		{
            $respose = $this->enableMFA('api', $request[$paramUsername]);
            if (isset($respose['@metadata']['statusCode']) && $respose['@metadata']['statusCode']==200) {
                return $this->response->success([], 200, 'MFA enabled successfully');
            } else {
                throw new HttpException(400, 'Error enabling the MFA.');
            } //End if
        } catch(Exception $e) {
			$message = 'Error enabling the MFA.';
			if ($e instanceof ValidationException) {
                $message = $e->errors();
            } else if ($e instanceof CognitoIdentityProviderException) {
				$message = $e->getAwsErrorMessage();
			} else {
                //Do nothing
            } //End if

			throw $e;
        } //Try-catch ends
    } //Function ends


	/**
	 * Action to disable MFA for the user
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 */
    public function actionApiDisableMFA(Request $request, string $paramUsername='username')
    {
		try
		{
            $respose = $this->disableMFA('api', $request[$paramUsername]);
            if (isset($respose['@metadata']['statusCode']) && $respose['@metadata']['statusCode']==200) {
                return $this->response->success([], 200, 'MFA disabled successfully');
            } else {
                throw new HttpException(400, 'Error disabling the MFA.');
            } //End if
        } catch(Exception $e) {
			$message = 'Error disabling the MFA.';
			if ($e instanceof ValidationException) {
                $message = $e->errors();
            } else if ($e instanceof CognitoIdentityProviderException) {
				$message = $e->getAwsErrorMessage();
			} else {
                //Do nothing
            } //End if

			throw $e;
        } //Try-catch ends
    } //Function ends


	/**
	 * Verify the MFA user code
	 * 
	 * @param  \Illuminate\Http\Request  $request
	 */
    public function actionApiVerifyMFA(Request $request, string $code)
    {
		try
		{
            return $this->verifyMFA('api', $code);
        } catch(Exception $e) {
			$message = 'Error activating the MFA.';
			if ($e instanceof ValidationException) {
                $message = $e->errors();
            } else if ($e instanceof CognitoIdentityProviderException) {
				$message = $e->getAwsErrorMessage();
			} else {
                //Do nothing
            } //End if

			throw $e;
        } //Try-catch ends
    } //Function ends


    /**
     * Authenticate using the MFA code using the API console
     */
    public function actionValidateMFA(Request $request)
    {
        try
        {
            //Create credentials object
            $collection = collect($request->all());
            $claim = $this->attemptLoginMFA($request, 'api', true);

            if ($claim instanceof AwsCognitoClaim) {
                return $this->response->success($claim->getData());
            } else {
                return $this->response->success($claim);
            } //End if

        } catch (Exception $e) {
            Log::error($e->getMessage());
            return $e;
        } //try-catch ends
    } //Function ends
    
} //Class ends