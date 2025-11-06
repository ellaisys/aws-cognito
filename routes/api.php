<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use Ellaisys\Cognito\Http\Controllers\Auth\LoginController;

use Ellaisys\Cognito\Http\Controllers\Api\UserController;
use Ellaisys\Cognito\Http\Controllers\Api\AuthController;
use Ellaisys\Cognito\Http\Controllers\Api\MFAController;
use Ellaisys\Cognito\Http\Controllers\Api\RegisterController;
use Ellaisys\Cognito\Http\Controllers\Api\RefreshTokenController;

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
    Route::post('/register', [RegisterController::class, 'actionRegister']);

    //Route group login
    Route::group(['prefix' => 'login'], function() {
        Route::post('/', [LoginController::class, 'login']);
        Route::post('/mfa', [MFAController::class, 'actionValidateMFA']);
    });

    //Authenticated routes
    Route::group(['middleware' => ['aws-cognito:api']], function() {

        //Route group user
        Route::group(['prefix' => 'user'], function() {
            //Route to get user profile
            Route::get('/profile', [UserController::class, 'actionGetRemoteUser']);

            //Route to invite a new user
            Route::post('/invite', [RegisterController::class, 'actionInvite']);
        });

        //Route group logout
        Route::group(['prefix' => 'logout'], function() {
            Route::put('/', [LoginController::class, 'logout']);
            Route::put('/forced', [LoginController::class, 'logoutForced']);
        });

        //Route group for MFA
        Route::group(['controller' => MFAController::class, 'prefix' => 'mfa'], function() {
            Route::post('/enable', 'actionApiEnableMFA');
            Route::post('/disable', 'actionApiDisableMFA');
            Route::get('/activate', 'actionApiActivateMFA');
            Route::post('/activate/{code}', 'actionApiVerifyMFA');
            Route::post('/deactivate', 'actionApiDeactivateMFA');
        });

        //Route for refresh token
        Route::post('/refresh-token', [RefreshTokenController::class, 'actionRefreshToken']);
    });
});
