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
    Route::get('/register',  function () { return view('cognito::auth.register'); })->name('form.register');
    Route::post('/register', [RegisterController::class, 'register'])->name('form.register.submit');

    //Route group login
    Route::group(['prefix' => 'login'], function() {
        Route::get('/', function () { return view('cognito::auth.login'); })->name('form.login');
        Route::post('/', [LoginController::class, 'login'])->name('form.login.submit');
        Route::post('/mfa', [LoginController::class, 'validateMFA'])->name('form.mfa.code.submit');
    });

    //Authenticated routes
    Route::group(['middleware' => ['aws-cognito:web']], function() {
        Route::get('/home', function () { return view('cognito::home'); })->name('home');

        //Route group logout
        Route::group(['prefix' => 'logout', 'controller' => LoginController::class], function() {
            Route::post('/', 'logout')->name('logout');
            Route::post('/forced', 'logoutForced')->name('logout_forced');
        });

        Route::group(['prefix' => 'user'], function() {
            Route::get('/changepassword', function () { return view('cognito::auth.change'); })->name('form.change.password');

            Route::group(['prefix' => 'mfa', 'controller' => MFAController::class], function() {
                Route::get('/activate', 'activate')->name('form.user.mfa.activate');
                Route::post('/verify', 'verify')->name('action.user.mfa.activate');
                Route::get('/deactivate', 'deactivate')->name('action.user.mfa.deactivate');
            });
        });
    });

});
