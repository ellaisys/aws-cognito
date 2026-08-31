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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Depends;

use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Services\JsonResponseService;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JsonResponseServiceTest extends TestCase
{
    private JsonResponseService $service;
    private array $payload;

    // Runs BEFORE every individual test method
    protected function setUp(): void
    {
        parent::setUp(); // Always good practice to call parent setup

        // Create a unique name and email for the test
        $name = 'Testbench Register User ' . date('dmy');
        $email = 'ellaisys+tb_register_' . date('dmyVHm') . '@gmail.com';

        $this->payload = [
            'name' => $name,
            'email' => $email
        ];

        $this->service = new JsonResponseService;
    } //Function ends

    /**
     * Test the success response of the JsonResponseService.
     */
    #[Test]
    public function test_basic_success_response(): void
    {
        $response = $this->service->success($this->payload);
        $this->assertNotNull($response);

        // Status code
        $this->assertEquals(200, $response->getStatusCode(), 'The status code should be 200');

        // Convert the response data to an array for easier assertions
        $responseData = $response->getData(true);

        // Assert the structure and content of the response data
        $this->testSuccessAssertions($responseData, $this->payload);
    } // Function ends

    /**
     * Test the success response of the JsonResponseService.
     */
    #[Test]
    #[Depends('test_basic_success_response')]
    public function test_basic_success_response_with_statuscode(): void
    {
        $response = $this->service->success($this->payload, 201);
        $this->assertNotNull($response);

        // Status code
        $this->assertEquals(201, $response->getStatusCode(), 'The status code should be 201');

        // Convert the response data to an array for easier assertions
        $responseData = $response->getData(true);

        // Assert the structure and content of the response data
        $this->testSuccessAssertions($responseData, $this->payload);
    } // Function ends

    /**
     * Test the success response of the JsonResponseService.
     */
    #[Test]
    #[Depends('test_basic_success_response')]
    public function test_basic_success_response_with_statuscode_and_message(): void
    {
        $message = 'Custom message';
        $response = $this->service->success($this->payload, 202, $message);
        $this->assertNotNull($response);

        // Status code
        $this->assertEquals(202, $response->getStatusCode(), 'The status code should be 201');

        // Convert the response data to an array for easier assertions
        $responseData = $response->getData(true);

        // Assert the structure and content of the response data
        $this->testSuccessAssertions($responseData, $this->payload, $message);
    } // Function ends

    /**
     * Assertions for the success response of the JsonResponseService.
     */
    private function testSuccessAssertions(array $responseData, array $payload, string $message = 'success'): void
    {
        // Assert the structure and content of the response data
        $this->assertArrayHasKey('status', $responseData, 'The response should contain a status key');
        $this->assertEquals('success', $responseData['status'], 'The status key should be success');
        $this->assertArrayHasKey('message', $responseData, 'The response should contain a message key');
        $this->assertEquals($message, $responseData['message'], 'The message key should be ' . $message);
        $this->assertArrayHasKey('error', $responseData, 'The response should contain an error key');
        $this->assertNull($responseData['error'], 'The error key should be null');
        $this->assertArrayHasKey('data', $responseData, 'The response should contain a data key');
        $this->assertEquals($payload, $responseData['data']);
        $this->assertIsArray($responseData['data'], 'The data key should contain an array');
        $this->assertNotEmpty($responseData['data'], 'The data key should not be empty');
    } // Function ends

    /**
     * Test the error response of the JsonResponseService.
     */
    #[Test]
    public function test_basic_error_response(): void
    {
        $message = 'Custom error message';
        $exception = new HttpException(400, $message);
        $response = $this->service->fail($exception, $this->payload);
        $this->assertNotNull($response);

        // Status code
        $this->assertEquals(400, $response->getStatusCode(), 'The status code should be 400');

        // Convert the response data to an array for easier assertions
        $responseData = $response->getData(true);

        // Assert the structure and content of the response data
        $this->testErrorAssertions($responseData);
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

} // Class ends
