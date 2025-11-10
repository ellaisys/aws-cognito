<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use Ellaisys\Cognito\Http\Controllers\Auth\LoginController;
use Ellaisys\Cognito\Http\Controllers\Auth\RegisterController;

use Ellaisys\Cognito\Http\Controllers\Api\UserController;
use Ellaisys\Cognito\Http\Controllers\Api\AuthController;
use Ellaisys\Cognito\Http\Controllers\Api\MFAController;
use Ellaisys\Cognito\Http\Controllers\Api\RefreshTokenController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "web" middleware group.
|
*/
Route::group(['prefix' => config('cognito.web_prefix', '')], function () {
    //Route to register a new user
    //Route::get('/register', view('auth.register'))->name('form.register');
    Route::post('/register', [RegisterController::class, 'register'])->name('form.register.submit');

    //Route group login
    Route::group(['prefix' => 'login'], function() {
        //Route::get('/', view('auth.login'))->name('form.login');
        Route::post('/', [LoginController::class, 'login'])->name('form.login.submit');
        Route::post('/mfa', [MFAController::class, 'actionValidateMFA']);
    });

    //Authenticated routes
    Route::group(['middleware' => ['aws-cognito:web']], function() {

        //Route group logout
        Route::group(['prefix' => 'logout', 'controller' => LoginController::class], function() {
            Route::put('/', 'logout')->name('logout');
            Route::put('/forced', 'logoutForced')->name('logout_forced');
        });
    });

});
