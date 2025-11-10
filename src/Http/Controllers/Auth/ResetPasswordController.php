<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;

//use Illuminate\Foundation\Auth\ResetsPasswords; //Removed for AWS Cognito
use Ellaisys\Cognito\Auth\ResetsPasswords; //Added for AWS Cognito

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    protected $redirectTo = '/home';
}
