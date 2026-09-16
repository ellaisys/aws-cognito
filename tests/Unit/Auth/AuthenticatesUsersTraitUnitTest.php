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
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('auth-trait')]
#[CoversTrait(AuthenticatesUsers::class)]
class AuthenticatesUsersTraitUnitTest extends TestCase
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
    public function test_challenge_method_with_validation_exception(array $data,
        string $expectedException): void
    {
        $this->expectException($expectedException);
        $this->class->challenge(request()->merge($data));
    } // Function ends

    /**
     * Data provider for test_challenge_method_with_validation_exception.
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
                'challenge_value' => 'dummy_value',
                'session' => 'dummy_session'],
                ValidationException::class
            ],
            'partial_challenge_data' => [
                ['challenge_name' => 'SELECT_CHALLENGE',
                'challenge_value' => 'dummy_value'],
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
                'challenge_value' => 'dummy_value',
                'session' => false],
                ValidationException::class
            ],
            'password_srp_wrong_session_data' => [
                ['challenge_name' => 'PASSWORD_SRP',
                'challenge_value' => 'dummy_value',
                'session' => ['dummy_session']],
                ValidationException::class
            ],
            'password_verifier_wrong_format_data' => [
                ['challenge_name' => 'PASSWORD_VERIFIER',
                'challenge_value' => 'dummy_value',
                'session' => 'dummy_session'],
                ValidationException::class
            ],
            'password_verifier_wrong_object_data' => [
                ['challenge_name' => 'PASSWORD_VERIFIER',
                'challenge_value' => '{"dummy_value":"abcd"}',
                'session' => 'dummy_session'],
                ValidationException::class
            ],
            'device_verifier_wrong_object_data' => [
                ['challenge_name' => 'DEVICE_PASSWORD_VERIFIER',
                'challenge_value' => '{"dummy_value":"abcd"}',
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

} // Class ends
