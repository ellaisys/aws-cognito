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

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\EncryptionTypes;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Traits\AwsCognitoClientHelper;
use Ellaisys\Cognito\Exceptions\AwsCognitoChallengeException;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('trait'), Group('user-auth')]
class AwsCognitoClientHelperTest extends TestCase
{
    private $client;

    const SAMPLE_DATA = [
    ];

    const SAMPLE_INVALID_DATA = [
        'username' => 'invalid_username',
        'access_token' => 'invalid_access_token',
        'pagination_token' => 'invalid_pagination_token',
        'session_value' => 'invalid_session_value_of_20_characters'
    ];

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        //Create AWS Cognito Client
        $this->client = app()->make(AwsCognitoClient::class);
    } // Function ends

    /**
     * Test if the buildChallengePayload method exists.
     */
    #[Test]
    public function test_method_buildChallengePayload_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'buildChallengePayload'));
    } // Function ends

    /**
     * Test the response of the buildChallengePayload method with invalid data.
     */
    #[Test]
    #[Depends('test_method_buildChallengePayload_exists')]
    #[DataProvider('challengeInvalidDataProvider')]
    public function test_buildChallengePayload_method_with_invalid_data(
        CognitoChallengeTypes $challengeType, string $challengeValue,
        string $username
    ): void
    {
        $this->expectException(HttpException::class);
        $this->client->buildChallengePayload(
            $challengeType,
            $challengeValue,
            $username
        );
    } // Function ends

    /**
     * Data provider for the test_buildChallengePayload_method_with_invalid_data test.
     */
    public static function challengeInvalidDataProvider(): array
    {
        return [
            'invalid challenge type' => [
                CognitoChallengeTypes::CUSTOM_CHALLENGE,
                'invalid_challenge_value',
                self::SAMPLE_INVALID_DATA['username']
            ],
            'invalid mfa value' => [
                CognitoChallengeTypes::SELECT_MFA_TYPE,
                'invalid_mfa_value',
                self::SAMPLE_INVALID_DATA['username']
            ],
            'invalid select challenge' => [
                CognitoChallengeTypes::SELECT_CHALLENGE,
                'invalid_select_challenge_value',
                self::SAMPLE_INVALID_DATA['username']
            ],
            'invalid password verifier' => [
                CognitoChallengeTypes::PASSWORD_VERIFIER,
                'invalid_password_verifier_value',
                self::SAMPLE_INVALID_DATA['username']
            ]
        ];
    } // Function ends

    /**
     * Test the response of the buildChallengePayload method with valid data.
     */
    #[Test]
    #[Depends('test_method_buildChallengePayload_exists')]
    #[DataProvider('challengeValidDataProvider')]
    public function test_buildChallengePayload_method_with_valid_data(
        CognitoChallengeTypes $challengeType, string $challengeValue,
        string $username, string $expectedParam): void
    {
        $response = $this->client->buildChallengePayload(
            $challengeType,
            $challengeValue,
            $username
        );
        $this->assertIsArray($response);
        $this->assertArrayHasKey($expectedParam, $response);
    } // Function ends

    /**
     * Data provider for the test_buildChallengePayload_method_with_valid_data test.
     */
    public static function challengeValidDataProvider(): array
    {
        $username = 'some_username';
        return [
            'password challenge' => [
                CognitoChallengeTypes::PASSWORD,
                'valid_password',
                $username,
                'PASSWORD'
            ],
            'password srp' => [
                CognitoChallengeTypes::PASSWORD_SRP,
                'valid_srp_value',
                $username,
                'SRP_A'
            ],
            'select mfa type' => [
                CognitoChallengeTypes::SELECT_MFA_TYPE,
                'SOFTWARE_TOKEN_MFA',
                $username,
                'ANSWER'
            ],
            'sms mfa' => [
                CognitoChallengeTypes::SMS_MFA,
                '123456',
                $username,
                'SMS_MFA_CODE'
            ],
            'software token mfa' => [
                CognitoChallengeTypes::SOFTWARE_TOKEN_MFA,
                '654321',
                $username,
                'SOFTWARE_TOKEN_MFA_CODE'
            ],
            'select challenge' => [
                CognitoChallengeTypes::SELECT_CHALLENGE,
                'EMAIL_OTP',
                $username,
                'ANSWER'
            ],
            'select challenge json data' => [
                CognitoChallengeTypes::SELECT_CHALLENGE,
                '{ "ANSWER": "value" }',
                $username,
                'ANSWER'
            ],
            'sms otp' => [
                CognitoChallengeTypes::SMS_OTP,
                '234567',
                $username,
                'SMS_OTP_CODE'
            ],
            'email otp' => [
                CognitoChallengeTypes::EMAIL_OTP,
                '345678',
                $username,
                'EMAIL_OTP_CODE'
            ],
            'web auth challenge' => [
                CognitoChallengeTypes::WEB_AUTHN,
                'some_web_auth_value',
                $username,
                'CREDENTIAL'
            ],
            'new password required' => [
                CognitoChallengeTypes::NEW_PASSWORD_REQUIRED,
                'new_password_value',
                $username,
                'NEW_PASSWORD'
            ],
            'password varifier' => [
                CognitoChallengeTypes::PASSWORD_VERIFIER,
                '["USERNAME", "PASSWORD_VERIFIER"]',
                $username,
                'USERNAME'
            ]

        ];
    } // Function ends

    /**
     * Test if the cognitoSecretHash method exists.
     */
    #[Test]
    public function test_method_cognitoSecretHash_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'cognitoSecretHash'));
    } // Function ends

    /**
     * Test the response of the getUser method with valid data.
     */
    #[Test]
    #[Depends('test_method_cognitoSecretHash_exists')]
    #[DataProvider('secretDataProvider')]
    public function test_cognitoSecretHash_method_with_valid_data(
        array $data): void
    {
        $response = $this->client->cognitoSecretHash(
            self::SAMPLE_INVALID_DATA['username'],
            $data
        );

        $this->assertIsArray($response);
    } // Function ends

    /**
     * Data provider for the test_cognitoSecretHash_method_with_valid_data test.
     */
    public static function secretDataProvider(): array
    {
        return [
            'auth parameters data' => [
                ['AuthParameters' => ['some_value']]
            ],
            'challenge responses data' => [
                ['ChallengeResponses' => ['some_value']]
            ],
            'empty parameters' => [
                []
            ]
        ];
    } // Function ends

    /**
     * Test if the buildInviteUserPayload method exists.
     */
    #[Test]
    public function test_method_buildInviteUserPayload_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'buildInviteUserPayload'));
    } // Function ends

    /**
     * Test the response of the respondToAuthChallenge method with valid data.
     */
    #[Test]
    #[Depends('test_method_buildInviteUserPayload_exists')]
    public function test_buildInviteUserPayload_method_with_valid_data(): void
    {
        // Get invalid credentials for testing
        $invalidCredentials = $this->getInvalidCredentials();

        // Set configuration
        Config::set('cognito.mfa_setup', 'OPTIONAL');

        $response = $this->client->buildInviteUserPayload(
            [],
            $invalidCredentials['password'],
            ['email' => 'invalid_email'],
            ['custom_metadata' => 'invalid_metadata'],
            'RESEND'
        );

        $this->assertIsArray($response);
    } // Function ends

    /**
     * Test the response of the respondToAuthChallenge method with invalid data.
     */
    #[Test]
    #[Depends('test_method_buildInviteUserPayload_exists')]
    public function test_buildInviteUserPayload_method_with_invalid_data(): void
    {
        // Get invalid credentials for testing
        $invalidCredentials = $this->getInvalidCredentials();

        // Set configuration
        Config::set('cognito.mfa_setup', 'OPTIONAL');
        Config::set('cognito.mfa_type', ['SMS_MFA']);

        $this->expectException(HttpException::class);
        $this->client->buildInviteUserPayload(
            [],
            $invalidCredentials['password'],
            ['email' => 'invalid_email'],
            ['custom_metadata' => 'invalid_metadata'],
            'RESEND'
        );
    } // Function ends
} // Class ends
