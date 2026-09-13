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

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\CoversClass;

use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Services\AwsCognitoSrpService;

use Exception;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[Group('unit'), Group('service')]
#[CoversClass(AwsCognitoSrpService::class)]
class AwsCognitoSrpServiceTest extends TestCase
{
    /**
     * @var AwsCognitoSrpService
     */
    private AwsCognitoSrpService $service;

    private static array $ephemeralResponse = [];

    // Runs BEFORE every individual test method
    protected function setUp(): void
    {
        parent::setUp(); // Always good practice to call parent setup

        $this->service = app()->make(AwsCognitoSrpService::class);
        $this->service->setIsDeviceAuth(false); // Disable device authentication
    } //Function ends

    /**
     * Test that the service instance is correctly created.
     */
    #[Test]
    public function test_service_instance(): void
    {
        $this->assertInstanceOf(AwsCognitoSrpService::class, $this->service);
    } // Function ends

    #[Test]
    #[Depends('test_service_instance')]
    public function test_service_generate_ephemeral_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'generateEphemeral'), 'The generateEphemeral method should exist in the service');
    } // Function ends

    /**
     * Test that the generateEphemeral method returns a valid response.
     */
    #[Test]
    #[Depends('test_service_generate_ephemeral_method_exists')]
    public function test_service_generate_ephemeral_method(?string $session=null): void
    {
        $response = $this->service->generateEphemeral($session);
        $this->assertNotNull($response, 'The generateEphemeral method should return a non-null response');
        $this->assertIsArray($response, 'The generateEphemeral method should return an array');
        $this->assertArrayHasKey('private_key', $response, 'The generateEphemeral method response should contain the key "private_key"');
        $this->assertArrayHasKey('public_key', $response, 'The generateEphemeral method response should contain the key "public_key"');
        $this->assertArrayHasKey('session_token', $response, 'The generateEphemeral method response should contain the key "session_token"');

        if ($session !== null) {
            $this->assertEquals($session, $response['session_token'], 'The generateEphemeral method response should contain the correct session token');
            self::$ephemeralResponse = $response;
        } //End if
    } // Function ends

    /**
     * Test that the generateEphemeral method returns a valid response when a
     * session token is provided.
     */
    #[Test]
    #[Depends('test_service_generate_ephemeral_method')]
    public function test_service_generate_ephemeral_method_with_session(): void
    {
        $session = 'test-session-random-key';
        $this->test_service_generate_ephemeral_method($session);
    } // Function ends

    /**
     * Test that the processChallenge method exists in the service.
     */
    #[Test]
    #[Depends('test_service_instance')]
    public function test_service_processChallenge_method_exists(): void
    {
        $this->assertTrue(method_exists($this->service, 'processChallenge'), 'The processChallenge method should exist in the service');
    } // Function ends

    /**
     * Test that the processChallenge method returns an exception when provided
     * with invalid input.
     */
    #[Test]
    #[Depends('test_service_processChallenge_method_exists')]
    public function test_service_processChallenge_method_returns_exception_with_invalid_input(): void
    {
        $challengeValue = '{}';
        $sessionKey = self::$ephemeralResponse['session_token'] ?? null;
        $challengeParams = '';

        $this->expectException(BadRequestHttpException::class);

        $this->service->processChallenge($challengeValue, $sessionKey, $challengeParams);
    } // Function ends

    /**
     * Test that the processChallenge method returns a valid response when provided
     * with valid input.
     */
    #[Test]
    #[Depends('test_service_processChallenge_method_exists')]
    public function test_service_processChallenge_method_returns_valid_response(): void
    {
        $challengeValue = '{"PASSWORD_CLAIM_SIGNATURE":"test-signature"}';
        $sessionKey = self::$ephemeralResponse['session_token'] ?? null;
        $challengeParams = '';

        $response = $this->service->processChallenge($challengeValue, $sessionKey, $challengeParams);
        $this->assertNotNull($response, 'Return a valid response with valid input');
        $this->assertEquals($challengeValue, json_encode($response), 'The challenge value in the response should match the input challenge value');
    } // Function ends

    /**
     * Test that the processChallenge method returns a valid response when provided
     * with valid input different from the previous test.
     */
    #[Test]
    #[Depends('test_service_processChallenge_method_exists')]
    public function test_service_processChallenge_method_returns_valid_response_with_different_input(): void
    {
        $challengeValue = '{"PASSKEY_HASH":"1234", "PASSWORD_CLAIM_SECRET_BLOCK":"1234"}';
        $sessionKey = self::$ephemeralResponse['session_token'] ?? null;
        $challengeParams = '{"PRIVATE_KEY":"test-private-key", "SALT":"abcd", "SRP_B":"1234", "USER_ID_FOR_SRP":"test-user-id"}';

        $response = $this->service->processChallenge($challengeValue, $sessionKey, $challengeParams);
        $this->assertNotNull($response, 'Return a valid response with different input');
        $this->assertArrayHasKey('PASSWORD_CLAIM_SIGNATURE', $response, 'The response should contain the PASSWORD_CLAIM_SIGNATURE key');
    } // Function ends

    /**
     * Test that the processChallenge method returns a valid response when provided
     * with valid input that includes device authentication parameters.
     */
    #[Test]
    #[Depends('test_service_processChallenge_method_exists')]
    public function test_service_processChallenge_method_returns_valid_response_with_device_auth(): void
    {
        $this->service->setIsDeviceAuth(true); // Enable device authentication

        $challengeValue = '{"PASSWORD_CLAIM_SECRET_BLOCK":"1234",
            "PRIVATE_KEY":"test-private-key", "PASSKEY_HASH":"1234",
            "MESSAGE_BASE64":"1234", "DEVICE_GROUP_KEY":"test-device-group-key", "DEVICE_KEY":"test-device-key"}';
        $sessionKey = self::$ephemeralResponse['session_token'] ?? null;
        $challengeParams = '{"SALT":"abcd", "SRP_B":"1234", "USER_ID_FOR_SRP":"test-user-id"}';

        $response = $this->service->processChallenge($challengeValue, $sessionKey, $challengeParams);
        $this->assertNotNull($response, 'Return a valid response for device authentication');
        $this->assertArrayHasKey('PASSWORD_CLAIM_SIGNATURE', $response, 'The response should contain the PASSWORD_CLAIM_SIGNATURE key');
    } // Function ends

} // Class ends
