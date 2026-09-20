<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Unit\Auth;

use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversTrait;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\EncryptionTypes;
use Ellaisys\Cognito\Auth\AuthenticatesUsers;
use Ellaisys\Cognito\Tests\Support\BaseAuthTraitFixture;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('auth-trait'), Group('login'), Group('srp')]
#[CoversTrait(AuthenticatesUsers::class)]
class AuthenticatesUsersUnitTest extends TestCase
{
    private $class;
    private Request $requestJson;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        // Set config values
        Config::set('cognito.allow_phone_number', false);
        Config::set('cognito.mfa_setup', 'OFF');
        Config::set('cognito.mfa_type', ['SOFTWARE_TOKEN_MFA']);
        Config::set('cognito.desired_delivery_mediums', ['EMAIL']);

        // Create a new fixture
        $this->class = new class {
            use AuthenticatesUsers;
        };

        // Create a Json request
        $this->requestJson = Request::create('/', 'POST', [], [], [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ]
        );
    } //Function ends

    /**
     * Test that the class is an instance of the anonymous class using AuthenticatesUsers.
     */
    #[Test]
    public function test_class_is_instance_of_anonymous_class(): void
    {
        $this->assertInstanceOf(get_class($this->class), $this->class);
    } // Function ends

    /**
     * Test that the attemptLogin method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_attemptLogin_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'attemptLogin'),
            'Method attemptLogin does not exist in the class.'
        );
    } // Function ends

    /**
     * Test that the attemptLoginSRP method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_attemptLoginSRP_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'attemptLoginSRP'),
            'Method attemptLoginSRP does not exist in the class.'
        );
    } // Function ends

    /**
     * Test that the attemptLoginSRP method throws an AwsCognitoException for
     * invalid config.
     */
    #[Test]
    #[Depends('test_method_attemptLoginSRP_exists_in_class')]
    public function test_method_attemptLoginSRP_with_invalid_config(): void
    {
        Config::set('cognito.allowed_auth_flows', [
            'ALLOW_USER_PASSWORD_AUTH',
        ]);

        $this->expectException(AwsCognitoException::class);

        $request = request()->merge([
            'username' => 'valid_user',
            'password' => 'valid_password',
        ]);

        $this->class->attemptLoginSRP($request);
    } // Function ends

    /**
     * Test that the attemptLoginSRP method throws a ValidationException
     * for invalid data.
     */
    #[Test]
    #[Depends('test_method_attemptLoginSRP_exists_in_class')]
    public function test_method_attemptLoginSRP_with_invalid_data(): void
    {
        Config::set('cognito.allowed_auth_flows', [
            'ALLOW_USER_SRP_AUTH',
        ]);

        $this->expectException(ValidationException::class);

        $request = request()->merge([
            'username' => 'valid_user',
            'password' => 'valid_password',
        ]);

        $this->class->attemptLoginSRP($request);
    } // Function ends

    /**
     * Test that the challenge method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_challenge_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'challenge'),
            'Method challenge does not exist in the class.'
        );
    } // Function ends

    /**
     * Test that the challenge method throws a ValidationException for invalid input.
     */
    #[Test]
    #[DataProvider('challengeExceptionDataProvider')]
    #[Depends('test_method_challenge_exists_in_class')]
    public function test_challenge_method_with_exception(array $data,
        string $expectedException): void
    {
        $this->expectException($expectedException);
        $this->class->challenge(request()->merge($data));
    } // Function ends

    /**
     * Data provider for test_challenge_method_with_exception.
     */
    public static function challengeExceptionDataProvider(): array
    {
        return [
            'null_data' => [[], ValidationException::class],
            'null_challenge_data' => [
                ['challenge_name' => null,
                'challenge_value' => null,
                'session' => null],
                ValidationException::class
            ],
            'empty_challenge_data' => [
                ['challenge_name' => '',
                'challenge_value' => '',
                'session' => ''],
                Exception::class
            ],
            'invalid_challenge_data' => [
                ['challenge_name' => 'INVALID_CHALLENGE',
                'challenge_value' => 'dummy_value1',
                'session' => 'dummy_session'],
                ValidationException::class
            ],
            'partial_challenge_data' => [
                ['challenge_name' => 'SELECT_CHALLENGE',
                'challenge_value' => 'dummy_value2'],
                ValidationException::class
            ],
            'select_challenge_data' => [
                ['challenge_name' => 'SELECT_CHALLENGE',
                'challenge_value' => 'PASSWORD_SRP',
                'session' => false],
                ValidationException::class
            ],
            'password_srp_wrong_format_data' => [
                ['challenge_name' => 'PASSWORD_SRP',
                'challenge_value' => 'dummy_value3',
                'session' => false],
                ValidationException::class
            ],
            'password_srp_wrong_session_data' => [
                ['challenge_name' => 'PASSWORD_SRP',
                'challenge_value' => 'dummy_value4',
                'session' => ['dummy_session']],
                \TypeError::class
            ],
            'password_verifier_wrong_format_data' => [
                ['challenge_name' => 'PASSWORD_VERIFIER',
                'challenge_value' => 'dummy_value5',
                'session' => 'dummy_session'],
                ValidationException::class
            ],
            'password_verifier_wrong_object_data' => [
                ['challenge_name' => 'PASSWORD_VERIFIER',
                'challenge_value' => '{"dummy_value":"abcd"}',
                'session' => 'dummy_session'],
                ValidationException::class
            ],
            'password_verifier_invalid_data_correct_format' => [
                ['challenge_name' => 'PASSWORD_VERIFIER',
                'challenge_value' => '{"PASSWORD_CLAIM_SECRET_BLOCK":"1234",
                "PRIVATE_KEY":"ed2241912d78c81a868f5f9d6607839f82292a9d5e6bcc01",
                "PASSKEY_HASH":"1234", "TIMESTAMP":"1234"}',
                'challenge_params' => '{"SALT":"abcd", "SRP_B":"1234",
                "USER_ID_FOR_SRP":"test-user-id"}',
                'session' => 'ed2241912d78c81a868f5f9d6607839f82292a9d5e6bcc01',
                'username' => 'dummy_username'],
                AwsCognitoException::class
            ],
            'device_verifier_wrong_object_data' => [
                ['challenge_name' => 'DEVICE_PASSWORD_VERIFIER',
                'challenge_value' => '{"some_dummy_value":"dcab"}',
                'session' => 'dummy_session'],
                ValidationException::class
            ]
        ];
    } // Function ends

    /**
     * Test that the logout method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_logout_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'logout'),
            'Method logout does not exist in the class.'
        );
    } // Function ends

    /**
     * Test that the logout method throws an exception.
     */
    #[Test]
    #[Depends('test_method_logout_exists_in_class')]
    public function test_method_logout_throws_exception(): void
    {
        $this->expectException(Exception::class);
        $this->class->logout(request());
    } // Function ends

} // Class ends
