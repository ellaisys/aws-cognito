<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use Ellaisys\Cognito\Http\Controllers\Auth\LoginController;
use Ellaisys\Cognito\Http\Controllers\Auth\RegisterController;
use Ellaisys\Cognito\Http\Controllers\Auth\VerificationController;
use Ellaisys\Cognito\Http\Controllers\Auth\MFAController;
use Ellaisys\Cognito\Http\Controllers\Auth\ForgotPasswordController;
use Ellaisys\Cognito\Http\Controllers\Auth\ResetPasswordController;
use Ellaisys\Cognito\Http\Controllers\Auth\RefreshTokenController;
use Ellaisys\Cognito\Http\Controllers\Auth\ConfirmPasswordController;
use Ellaisys\Cognito\Http\Controllers\Auth\WebAuthPasskeyController;

use Ellaisys\Cognito\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['prefix' => config('cognito.api_prefix', ''),
    'headers' => ['Accept' => 'application/json']], function () {
    //Route to register a new user
    Route::group(['prefix' => 'register'], function() {
        Route::post('/', [RegisterController::class, 'actionRegister']);
        Route::post('/verify', [VerificationController::class, 'verify']);
        Route::post('/resend-code', [VerificationController::class, 'resend']);
    });

    //Route group login
    Route::group(['prefix' => 'login'], function() {
        Route::post('/', [LoginController::class, 'login']);
        Route::post('/challenge', [LoginController::class, 'challenge']);
        Route::get('/passkey/challenge', [WebAuthPasskeyController::class, 'challenge']);
        Route::get('/passkey/challenge/{challengeName}', [WebAuthPasskeyController::class, 'challenge']);
        Route::post('/passkey/challenge', [WebAuthPasskeyController::class, 'challenge']);
    });

    //Forgot password routes
    Route::group(['prefix' => 'password'], function() {
        Route::post('/forgot', [ForgotPasswordController::class, 'sendResetLink']);
        Route::post('/reset', [ResetPasswordController::class, 'reset']);
    });

    //Authenticated routes
    Route::group(['middleware' => ['aws-cognito']], function() {

        //Route group user
        Route::group(['prefix' => 'user'], function() {
            //Route to get user profile
            Route::get('/profile', [UserController::class, 'actionGetRemoteUser']);

            //Route to invite a new user
            Route::post('/invite', [RegisterController::class, 'actionInvite']);

            //Change password
            Route::post('/changepassword', [ConfirmPasswordController::class, 'change']);

            //Route group for MFA
            Route::group(['prefix' => 'mfa', 'controller' => MFAController::class], function() {
                Route::get('/activate', 'activate');
                Route::post('/activate/{code}', 'verify');
                Route::post('/deactivate', 'deactivate');
                Route::post('/enable', 'enable');
                Route::post('/disable', 'disable');
            });

            //Route to passkeys
            Route::group(['prefix' => 'passkey', 'controller' => WebAuthPasskeyController::class], function() {
                Route::get('/start', 'start');
                Route::post('/complete', 'complete');
                Route::delete('/', 'delete');
            });
        });

        //Route group logout
        Route::group(['prefix' => 'logout', 'controller' => LoginController::class], function() {
            Route::put('/', 'logout');
            Route::put('/forced', 'logoutForced');
        });

        //Route group for MFA
        Route::group(['prefix' => 'mfa', 'controller' => MFAController::class], function() {
            Route::post('/enable', 'enable');
            Route::post('/disable', 'disable');
        });

        //Route for refresh token
        Route::post('/token/refresh', [RefreshTokenController::class, 'revalidate']);
    });
});
