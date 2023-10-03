<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use Ellaisys\Cognito\Http\Controllers\Api\ApiAuthController;
use Ellaisys\Cognito\Http\Controllers\Api\ApiMFAController;

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

Route::group(['prefix' => 'user'], function () {
    Route::post('register', [ApiAuthController::class, 'actionRegister']);

    //Route group login
    Route::group(['prefix' => 'login'], function() {
        Route::put('/', [ApiAuthController::class, 'actionLogin']);
        Route::put('/mfa', [ApiMFAController::class, 'actionValidateMFA']);
    });

    //Authenticated routes
    Route::group(['middleware' => 'aws-cognito'], function() {
        //Route::get('profile', [AuthController::class, 'getRemoteUser']);

        //Route group logout
        Route::group(['prefix' => 'logout'], function() {
            Route::put('/', [ApiAuthController::class, 'actionLogout']);
            Route::put('/forced', [ApiAuthController::class, 'actionLogoutForced']);
        });        

        //Route group for MFA
        Route::group(['controller' => ApiMFAController::class, 'prefix' => 'mfa'], function() {
            Route::post('/enable', 'actionApiEnableMFA');
            Route::post('/disable', 'actionApiDisableMFA');
            Route::get('/activate', 'actionApiActivateMFA');
            Route::post('/activate/{code}', 'actionApiVerifyMFA');
            Route::post('/deactivate', 'actionApiDeactivateMFA');
        });

        //Route for refresh token
        //Route::post('refresh-token', [ResetController::class, 'actionRefreshToken']);
    });
});


