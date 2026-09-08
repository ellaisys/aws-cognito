<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Unit;

use Illuminate\Support\Facades\Config;
use Illuminate\Http\Response;
use Illuminate\Http\Resources\Json\JsonResource;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DataProvider;

use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Services\JsonResponseService;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class JsonResponseServiceTest extends TestCase
{
    private static array $payload = [
        'name' => 'Testbench Register Temp User',
        'email' => 'ellaisys+tb_register_tmp@gmail.com'
    ];

    private JsonResponseService $service;

    // Runs BEFORE every individual test method
    protected function setUp(): void
    {
        parent::setUp(); // Always good practice to call parent setup

        $this->service = new JsonResponseService;
    } //Function ends

    /**
     * Test the success response of the JsonResponseService.
     */
    #[Test]
    #[DataProvider('successDataProvider')]
    public function test_basic_success_response(
        mixed $payload, ?int $statusCode = null, ?string $message = null): void
    {
        $response = $this->service->success($payload, $statusCode ?? 200, $message ?? 'success');
        $this->assertNotNull($response);

        // Handle Cognito metadata if present
        if (is_array($payload) && array_key_exists('@metadata', $payload)) {
            $metadata = $payload['@metadata'];
            $statusCode = $metadata['statusCode'] ?? $statusCode;
            unset($payload['@metadata']);
        } // End if

        // Status code
        $this->assertEquals($statusCode ?? 200, $response->getStatusCode(), 'The status code should be 200');

        // Convert the response data to an array for easier assertions
        $responseData = $response->getData(true);

        // Assert the structure and content of the response data
        $this->testSuccessAssertions($responseData, $payload, $message ?? 'success');
    } // Function ends

    /**
     * Data provider for the success response tests.
     *
     * @return array The data sets for the success response tests.
     */
    public static function successDataProvider(): array
    {
        // Create a payload that includes Cognito metadata for testing purposes
        $cognitoPayload = array_merge(self::$payload, ['@metadata' => [
            'requestId' => '1234567890',
            'attempts' => 1,
            'statusCode' => 200,
        ]]);

        return [
            'success_with_array_payload' => [ self::$payload, null, null ],
            'success_with_array_payload_and_statuscode' => [ self::$payload, 201, null ],
            'success_with_array_payload_statuscode_and_message' => [ self::$payload, 202, 'Custom message' ],
            'success_with_array_payload_and_message' => [ self::$payload, null, 'Custom message' ],
            'success_with_collection_object_payload' => [ collect(self::$payload), null, null ],
            'success_with_object_payload' => [ (object) self::$payload, null, null ],
            'success_with_empty_payload' => [ [], null, null ],
            'success_with_cognito_payload' => [ $cognitoPayload, null, null ],
            'success_with_cognito_payload_statuscode' => [ $cognitoPayload, 201, null ],
        ];
    } // Function ends

    /**
     * Assertions for the success response of the JsonResponseService.
     */
    private function testSuccessAssertions(array $responseData, mixed $payload, string $message = 'success'): void
    {
        // Assert the structure and content of the response data
        $this->assertArrayHasKey('status', $responseData, 'The response should contain a status key');
        $this->assertEquals('success', $responseData['status'], 'The status key should be success');
        $this->assertArrayHasKey('message', $responseData, 'The response should contain a message key');
        $this->assertEquals($message, $responseData['message'], 'The message key should be ' . $message);
        $this->assertArrayHasKey('error', $responseData, 'The response should contain an error key');
        $this->assertNull($responseData['error'], 'The error key should be null');
        $this->assertArrayHasKey('data', $responseData, 'The response should contain a data key');
        $this->assertIsArray($responseData['data'], 'The data key should contain an array');

        // Check the type of the payload and assert accordingly
        if (is_array($payload)) {
            $this->assertEquals($payload, $responseData['data'], 'The data key should match the array payload');
        } elseif (is_object($payload) && method_exists($payload, 'toArray')) {
            $this->assertEquals($payload->toArray(), $responseData['data'], 'The data key should match the object payload converted to array');
        } else {
            $this->assertEquals([], $responseData['data'], 'The data key should be an empty array');
        } // End if

        if (!empty($payload) && is_array($payload)) {
            $this->assertNotEmpty($responseData['data'], 'The data key should not be empty');
        } // End if
    } // Function ends

    /**
     * Test the error response of the JsonResponseService.
     */
    #[Test]
    public function test_basic_error_response(?Exception $exception = null): void
    {
        $message = 'Custom error message';
        $exception = $exception ?? new HttpException(400, $message);
        $response = $this->service->fail($exception, self::$payload);
        $this->assertNotNull($response);

        // Status code
        $this->assertEquals(400, $response->getStatusCode(), 'The status code should be 400');

        // Convert the response data to an array for easier assertions
        $responseData = $response->getData(true);

        // Assert the structure and content of the response data
        $this->testErrorAssertions($responseData);
    } // Function ends

    /**
     * Test the error response of the JsonResponseService.
     */
    #[Test]
    public function test_basic_error_response_debug_setting(): void
    {
        Config::set('app.debug', true);
        $this->test_basic_error_response();
    } // Function ends

    public function test_basic_error_response_with_cognito_exception(): void
    {
        $previous = $this->createMock(
                CognitoIdentityProviderException::class
            );

        $previous
            ->expects($this->once())
            ->method('getAwsErrorCode')
            ->willReturn('UsernameExistsException');

        $previous
            ->expects($this->once())
            ->method('getAwsErrorMessage')
            ->willReturn('User already exists.');

        $message = 'Cognito error message';
        $exception = new HttpException(400, $message, $previous);

        $this->test_basic_error_response($exception);
    } // Function ends

    /**
     * Assertions for the error response of the JsonResponseService.
     */
    private function testErrorAssertions(array $responseData, ?string $errorMessage=null): void
    {
        $this->assertArrayHasKey('status', $responseData, 'The response should contain a status key');
        $this->assertEquals('error', $responseData['status'], 'The status key should be error');
        $this->assertArrayHasKey('message', $responseData, 'The response should contain a message key');
        $this->assertEquals($errorMessage, $responseData['message'], 'The message key should be ' . ($errorMessage ?? 'null'));
        $this->assertArrayHasKey('error', $responseData, 'The response should contain an error key');
        $this->assertNotNull($responseData['error'], 'The error key should not be null');
        $this->assertArrayHasKey('data', $responseData, 'The response should contain a data key');
    } // Function ends

    /**
     * Test the no content response of the JsonResponseService.
     */
    #[Test]
    public function test_no_content_response(): void
    {
        $response = $this->service->noContent();
        $this->assertNotNull($response);

        // Status code
        $this->assertEquals(Response::HTTP_NO_CONTENT, $response->getStatusCode(), 'The status code should be 204');
    } // Function ends

    /**
     * Test the no content response with a JSON resource.
     */
    #[Test]
    public function test_no_content_response_with_json_resource(): void
    {
        $response = $this->service->noContent(new JsonResource([]));
        $this->assertNotNull($response);

        // Status code
        $this->assertEquals(Response::HTTP_NO_CONTENT,
            $response->getStatusCode(), 'The status code should be 204');
    } // Function ends

    /**
     * Test the no content response with a wrong payload.
     */
    #[Test]
    public function test_no_content_response_wrong_payload(): void
    {
        $this->expectException(HttpException::class);
        $this->service->noContent('invalid payload');
    } // Function ends

} // Class ends
