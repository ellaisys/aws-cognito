<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use Ellaisys\Cognito\Http\Controllers\Auth\LoginController;
use Ellaisys\Cognito\Http\Controllers\Auth\RegisterController;
use Ellaisys\Cognito\Http\Controllers\Auth\MFAController;

use Ellaisys\Cognito\Http\Controllers\Api\UserController;
use Ellaisys\Cognito\Http\Controllers\Api\AuthController;

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
    Route::post('/register', [RegisterController::class, 'register']);

    //Route group login
    Route::group(['prefix' => 'login'], function() {
        Route::post('/', [LoginController::class, 'login']);
        Route::post('/mfa', [MFAController::class, 'actionValidateMFA']);
    });

    //Authenticated routes
    Route::group(['middleware' => ['aws-cognito']], function() {

        //Route group user
        Route::group(['prefix' => 'user'], function() {
            //Route to get user profile
            Route::get('/profile', [UserController::class, 'actionGetRemoteUser']);

            //Route to invite a new user
            Route::post('/invite', [RegisterController::class, 'actionInvite']);

            //Route group for MFA
            Route::group(['prefix' => 'mfa', 'controller' => MFAController::class], function() {
                Route::get('/activate', 'activate');
                Route::post('/activate/{code}', 'verify');
                Route::post('/deactivate', 'deactivate');
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
        Route::post('/refresh-token', [RefreshTokenController::class, 'actionRefreshToken']);
    });
});
