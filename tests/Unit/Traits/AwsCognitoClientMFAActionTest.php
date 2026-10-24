<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Unit\Traits;

use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversTrait;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\EncryptionTypes;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Traits\AwsCognitoClientMFAAction;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('trait'), Group('mfa')]
#[CoversTrait(AwsCognitoClientMFAAction::class)]
class AwsCognitoClientMFAActionTest extends TestCase
{
    private $client;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        //Create AWS Cognito Client
        $this->client = app()->make(AwsCognitoClient::class);
    } // Function ends

    /**
     * Test if the associateSoftwareTokenMFA method exists.
     */
    #[Test]
    public function test_method_associateSoftwareTokenMFA_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'associateSoftwareTokenMFA'));
    } // Function ends

    /**
     * Test the response of the associateSoftwareTokenMFA method with invalid
     * access token.
     */
    #[Test]
    #[Depends('test_method_associateSoftwareTokenMFA_exists')]
    public function test_associateSoftwareTokenMFA_method_with_invalid_access_token(): void
    {
        $this->expectException(HttpException::class);
        $this->client->associateSoftwareTokenMFA(
            TestCase::SAMPLE_INVALID_DATA['access_token']
        );
    } // Function ends

    /**
     * Test the response of the associateSoftwareTokenMFA method with invalid
     * session.
     */
    #[Test]
    #[Depends('test_method_associateSoftwareTokenMFA_exists')]
    public function test_associateSoftwareTokenMFA_method_with_invalid_session(): void
    {
        $this->expectException(HttpException::class);
        $this->client->associateSoftwareTokenMFA(
            null,
            TestCase::SAMPLE_INVALID_DATA['session_value']
        );
    } // Function ends

    /**
     * Test if the verifySoftwareTokenMFA method exists.
     */
    #[Test]
    public function test_method_verifySoftwareTokenMFA_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'verifySoftwareTokenMFA'));
    } // Function ends

    /**
     * Test the response of the verifySoftwareTokenMFA method with invalid
     * access token.
     */
    #[Test]
    #[Depends('test_method_verifySoftwareTokenMFA_exists')]
    public function test_verifySoftwareTokenMFA_method_with_invalid_access_token(): void
    {
        $this->expectException(HttpException::class);
        $this->client->verifySoftwareTokenMFA(
            TestCase::SAMPLE_INVALID_DATA['code'],
            TestCase::SAMPLE_INVALID_DATA['access_token']
        );
    } // Function ends

    /**
     * Test the response of the verifySoftwareTokenMFA method with invalid
     * session.
     */
    #[Test]
    #[Depends('test_method_verifySoftwareTokenMFA_exists')]
    public function test_verifySoftwareTokenMFA_method_with_invalid_session(): void
    {
        $this->expectException(HttpException::class);
        $this->client->verifySoftwareTokenMFA(
            TestCase::SAMPLE_INVALID_DATA['code'],
            null,
            TestCase::SAMPLE_INVALID_DATA['session_value']
        );
    } // Function ends

    /**
     * Test if the setUserMFAPreference method exists.
     */
    #[Test]
    public function test_method_setUserMFAPreference_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'setUserMFAPreference'));
    } // Function ends

    /**
     * Test the response of the setUserMFAPreference method with invalid
     * access token.
     */
    #[Test]
    #[Depends('test_method_setUserMFAPreference_exists')]
    public function test_setUserMFAPreference_method_with_invalid_access_token(): void
    {
        $this->expectException(HttpException::class);
        $this->client->setUserMFAPreference(
            TestCase::SAMPLE_INVALID_DATA['access_token']
        );
    } // Function ends

    /**
     * Test if the adminSetUserMFAPreference method exists.
     */
    #[Test]
    public function test_method_adminSetUserMFAPreference_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminSetUserMFAPreference'));
    } // Function ends

    /**
     * Test the response of the adminSetUserMFAPreference method with invalid
     * configuration.
     */
    #[Test]
    #[Depends('test_method_adminSetUserMFAPreference_exists')]
    public function test_adminSetUserMFAPreference_method_with_invalid_configuration(): void
    {
        // Set configuration for the test
        Config::set('cognito.mfa_type', []);

        $this->expectException(HttpException::class);
        $this->client->adminSetUserMFAPreference(
            TestCase::SAMPLE_INVALID_DATA['access_token'],
            true
        );
    } // Function ends

    /**
     * Test the response of the adminSetUserMFAPreference method with invalid
     * username but true flag
     */
    #[Test]
    #[Depends('test_method_adminSetUserMFAPreference_exists')]
    public function test_adminSetUserMFAPreference_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminSetUserMFAPreference(
            TestCase::SAMPLE_INVALID_DATA['username'],
            true
        );
    } // Function ends

    /**
     * Test if the getUserPoolMfaConfig method exists.
     */
    #[Test]
    public function test_method_getUserPoolMfaConfig_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'getUserPoolMfaConfig'));
    } // Function ends

    /**
     * Test the response of the getUserPoolMfaConfig method with invalid data
     */
    #[Test]
    #[Depends('test_method_getUserPoolMfaConfig_exists')]
    public function test_getUserPoolMfaConfig_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->getUserPoolMfaConfig(
            TestCase::SAMPLE_INVALID_DATA['user_pool_id']
        );
    } // Function ends

    /**
     * Test the response of the getUserPoolMfaConfig method with valid data
     */
    #[Test]
    #[Depends('test_method_getUserPoolMfaConfig_exists')]
    public function test_getUserPoolMfaConfig_method_with_valid_data(): void
    {
        $response = $this->client->getUserPoolMfaConfig();
        $data = $response->toArray();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('MfaConfiguration', $data);
    } // Function ends

    /**
     * Test if the setUserPoolMfaConfig method exists.
     */
    #[Test]
    public function test_method_setUserPoolMfaConfig_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'setUserPoolMfaConfig'));
    } // Function ends

    /**
     * Test the response of the setUserPoolMfaConfig method with invalid data.
     */
    #[Test]
    #[Depends('test_method_setUserPoolMfaConfig_exists')]
    public function test_setUserPoolMfaConfig_method_with_invalid_data(): void
    {
        // Set configuration with invalid data
        Config::set('cognito.mfa_setup', 'OPTIONAL');
        Config::set('cognito.mfa_type', ['EMAIL_MFA', 'SMS_MFA', 'WEB_AUTHN']);

        $this->expectException(HttpException::class);
        $this->client->setUserPoolMfaConfig(
            TestCase::SAMPLE_INVALID_DATA['user_pool_id']
        );
    } // Function ends

    /**
     * Test the response of the setUserPoolMfaConfig method with valid data.
     */
    #[Test]
    #[Depends('test_method_setUserPoolMfaConfig_exists')]
    public function test_setUserPoolMfaConfig_method_with_valid_data(): void
    {
        $response = $this->client->setUserPoolMfaConfig();
        $data = $response->toArray();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('MfaConfiguration', $data);
    } // Function ends

} // Class ends
