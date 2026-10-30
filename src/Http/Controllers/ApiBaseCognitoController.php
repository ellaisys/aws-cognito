<?php

namespace Ellaisys\Cognito\Http\Controllers;

use Ellaisys\Cognito\Services\JsonResponseService;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Routing\Controller as BaseController;

class ApiBaseCognitoController extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    /**
     * @var \Modules\Core\Services\JsonResponseService
     */
    protected $response;


    /**
     * MainController constructor.
     */
    public function __construct()
    {
        $this->response = new JsonResponseService;
    }

} //Class end
