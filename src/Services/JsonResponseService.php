<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Services;

use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

/**
 * Class JsonResponseService
 * @package Modules\Core\Services
 */
class JsonResponseService
{
    /**
     * @param array $resource
     * @param int $code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function success($resource, $statusCode = Response::HTTP_OK, string $message='success')
    {
        //Modify if the resource is not an array
        if (!is_array($resource)) {
            //convert model to array
            if (is_object($resource) && method_exists($resource, 'toArray')) {
                $resource = $resource->toArray();
            } else {
                $resource = [];
            } //End if
        } //End if

        return $this->putAdditionalMeta(
                $resource, 'success', null,
                $statusCode, $message
            )
            ->response()
            ->setStatusCode($statusCode);
    } //Function end


    /**
     * @param array $resource
     * @param int $code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function fail($exception, array $resource = [],
        $statusCode = Response::HTTP_BAD_REQUEST,
        string $message = null, string $errorKey = null)
    {
        return $this->putAdditionalMeta(
                $resource, 'error', $exception,
                $statusCode, $message, $errorKey
            )
            ->response()
            ->setStatusCode($statusCode);
    } //Function end


    /**
     * @param array $resource
     * @param int $code
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function noContent($resource = [], $code = Response::HTTP_NO_CONTENT)
    {
        return $this->putAdditionalMeta($resource, 'success')
            ->response()
            ->setStatusCode($code);
    } //Function end


    /**
     * @param $resource
     * @param $status
     *
     * @return \Illuminate\Http\Resources\Json\JsonResource
     */
    private function putAdditionalMeta($resource, $status, $e = null,
        $statusCode = Response::HTTP_BAD_REQUEST,
        string $message = null, string $errorKey = null)
    {
        $meta   = [
            'message'        => $message,
            'status'         => $status,
            'error'          => null,
            'execution_time' => number_format(microtime(true) - LARAVEL_START, 4),
        ];

        //Add exception message
        if ((!empty($e)) && ($e instanceof Exception)) {
            $systemErrorCode = $e->getCode();
            $systemErrorMsg = $e->getMessage();
            $parentError = $e->getPrevious();
            if ($parentError instanceof CognitoIdentityProviderException) {
                $systemErrorCode = $parentError->getAwsErrorCode();
                $systemErrorMsg = $parentError->getAwsErrorMessage();
            }

            $meta['error'] = [
                'code' => $statusCode,
                'message' => $message,
                'key' => $errorKey,
                'system_code' => $systemErrorCode,
                'system_message' => $systemErrorMsg,
            ];
        } //End if

        $merged = array_merge($resource->additional ?? [], $meta);

        if ($resource instanceof JsonResource) {
            return $resource->additional($merged);
        }

        if (is_array($resource)) {
            return (
                new JsonResource(
                    collect($resource)
                ))->additional($merged);
        }

        throw new HttpException('Resource must be an array or an instance of JsonResource');
    } //Function end

} //Class end
