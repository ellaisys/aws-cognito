<?php

namespace Ellaisys\Cognito\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use Ellaisys\Cognito\Services\JsonResponseService;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

use Exception;
use Throwable;

class BaseCognitoController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @var \Modules\Core\Services\JsonResponseService
     */
    protected $response;


    /**
     * Default constructor.
     */
    public function __construct()
    {
        $this->response = new JsonResponseService;
    }

    protected function isJson(Request $request): bool
    {
        try {
            return $request->expectsJson() || $request->isJson();
        } catch (Exception $e) {
            Log::error('BaseCognitoController:isJson:Exception');
            return false;
        } //Try-Catch Ends
    } //Function ends

} //Class end
