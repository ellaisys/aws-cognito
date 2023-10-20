<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::group(['prefix' => config('cognito.api_prefix', '')], function () {
    Route::post('/register', [RegisterController::class, 'actionRegister']);

    //Route group login
    Route::group(['prefix' => 'login'], function() {
        Route::post('/', [AuthController::class, 'actionLogin']);
        Route::post('/mfa', [MFAController::class, 'actionValidateMFA']);
    });

    //Authenticated routes
    Route::group(['middleware' => 'aws-cognito'], function() {
        Route::get('profile', [UserController::class, 'actionGetRemoteUser']);

        //Route group logout
        Route::group(['prefix' => 'logout'], function() {
            Route::put('/', [AuthController::class, 'actionLogout']);
            Route::put('/forced', [AuthController::class, 'actionLogoutForced']);
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
