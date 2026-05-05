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
    Route::group(['prefix' => 'register'], function() {
        Route::get('/',  function () { return view('cognito::pages.auth.registers.register'); })->name('form.register');
        Route::post('/', [RegisterController::class, 'register'])->name('action.register.submit');
        Route::get('/verify',  function () { return view('cognito::pages.auth.registers.verify'); })->name('form.register.verify');
        Route::post('/verify', [VerificationController::class, 'verify'])->name('action.register.verify');
        Route::get('/resend-code',  function () { return view('cognito::pages.auth.registers.resend'); })->name('form.register.resend_code');
        Route::post('/resend-code', [VerificationController::class, 'resend'])->name('action.register.resend_code');
    });

    //Forgot password
    Route::group(['prefix' => 'password'], function() {
        Route::get('/forgot',  function () { return view('cognito::pages.auth.passwords.email'); })->name('form.password.forgot');
        Route::post('/forgot', [ForgotPasswordController::class, 'sendResetLink'])->name('action.password.forgot');
        Route::get('/reset',  function () { return view('cognito::pages.auth.passwords.reset'); })->name('form.password.reset');
        Route::post('/reset', [ResetPasswordController::class, 'reset'])->name('action.password.reset');
    });

    //Route group login
    Route::group(['prefix' => 'login'], function() {
        Route::get('/', function () { return view('cognito::pages.auth.login'); })->name('form.login');
        Route::post('/', [LoginController::class, 'login'])->name('action.login.submit');
        Route::post('/auth-challenge', [LoginController::class, 'challenge'])->name('action.auth.challenge.submit');
        Route::any('/{step}', function (string $step) {
            return view('cognito::pages.auth.login', ['step' => $step]);
        });
        Route::post('/passkey/challenge', [WebAuthPasskeyController::class, 'challenge'])->name('action.auth.passkey.challenge');
    });

    //Authenticated routes
    Route::group(['middleware' => ['aws-cognito']], function() {
        Route::get('/home', function () { return view('cognito::home'); })->name('home');

        //Route for refresh session
        Route::post('/session/refresh', [RefreshTokenController::class, 'revalidate']);

        //Route group logout
        Route::group(['prefix' => 'logout', 'controller' => LoginController::class], function() {
            Route::post('/', 'logout')->name('logout');
            Route::post('/forced', 'logoutForced')->name('logout_forced');
        });

        Route::group(['prefix' => 'user'], function() {
            Route::get('/changepassword', function () { return view('cognito::pages.auth.passwords.change'); })->name('form.change.password');
            Route::post('/changepassword', [ConfirmPasswordController::class, 'change'])->name('action.change.password');
            Route::get('/invite', function () { return view('cognito::pages.auth.invite'); })->name('form.user.invite');
            Route::post('/invite', [RegisterController::class, 'invite'])->name('action.invite.submit');

            Route::group(['prefix' => 'mfa', 'controller' => MFAController::class], function() {
                Route::get('/activate', 'activate')->name('form.user.mfa.activate');
                Route::post('/verify', 'verify')->name('action.user.mfa.activate');
                Route::get('/deactivate', 'deactivate')->name('action.user.mfa.deactivate');
                Route::get('/enable', 'enable')->name('action.mfa.enable');
                Route::get('/disable', 'disable')->name('action.mfa.disable');
            });

            //Route to passkeys
            Route::group(['prefix' => 'passkey', 'controller' => WebAuthPasskeyController::class], function() {
                Route::post('/start', 'start')->name('action.user.passkey.start');
                Route::post('/complete', 'complete')->name('action.user.passkey.complete');
                Route::delete('/', 'delete')->name('action.user.passkey.delete');
            });
        });
    });

});
