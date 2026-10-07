<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\Auth\RegisterMFA;

use Ellaisys\Cognito\Http\Controllers\BaseCognitoController as Controller;

use Exception;

class MFAController extends Controller
{
    use RegisterMFA;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('aws-cognito');

        // Controller action flag
        $this->setIsControllerAction(false);

        parent::__construct();
    }

} //Class ends
