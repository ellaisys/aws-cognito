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
use Ellaisys\Cognito\Auth\ConfirmsPasswords;
use Ellaisys\Cognito\Tests\Support\BaseAuthTraitFixture;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('auth-trait'), Group('password')]
#[CoversTrait(ConfirmsPasswords::class)]
class ConfirmsPasswordsUnitTest extends TestCase
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
            use ConfirmsPasswords;
        };

        // Create a Json request
        $this->requestJson = Request::create('/', 'POST', [], [], [],
            [
                'HTTP_ACCEPT' => TestCase::APPLICATION_JSON,
                'CONTENT_TYPE' => TestCase::APPLICATION_JSON,
            ]
        );
    } //Function ends

    /**
     * Test that the class is an instance of the anonymous class using ConfirmsPasswords.
     */
    #[Test]
    public function test_class_is_instance_of_anonymous_class(): void
    {
        $this->assertInstanceOf(get_class($this->class), $this->class);
    } // Function ends

    /**
     * Test that the 'confirm' method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_confirm_exists(): void
    {
        $this->assertTrue(method_exists($this->class, 'confirm'));
    } // Function ends

    /**
     * Test the confirm method with invalid data using the data provider.
     *
     * @dataProvider confirmMethodExceptionDataProvider
     */
    #[Test]
    #[Depends('test_method_confirm_exists')]
    #[DataProvider('confirmMethodExceptionDataProvider')]
    public function test_method_confirm_with_invalid_data(array $data,
        string $expectedException): void
    {
        $request = $this->requestJson;
        $request->merge($data);
        $this->expectException($expectedException);
        $this->class->confirm($request);
    } // Function ends

    /**
     * Data provider for test_method_confirm_with_invalid_data.
     */
    public static function confirmMethodExceptionDataProvider(): array
    {
        return [
            'no data' => [
                [],
                InvalidUserException::class,
            ],
            'invalid challenge name' => [
                ['challenge' => ''],
                InvalidUserException::class,
            ],
            'invalid challenge value' => [
                ['challenge_name' => 'invalid'],
                InvalidUserException::class,
            ],
            'valid challenge and missing password' => [
                ['challenge_name' => 'NEW_PASSWORD_REQUIRED'],
                ValidationException::class,
            ],
            'invalid password format' => [
                [
                    'challenge_name' => 'NEW_PASSWORD_REQUIRED',
                    'password' => 'InvalidPasswordFormat'
                ],
                ValidationException::class,
            ],
            'valid password format missing new password' => [
                [
                    'challenge_name' => 'NEW_PASSWORD_REQUIRED',
                    'password' => 'ValidPassword!123'
                ],
                ValidationException::class,
            ],
            'valid password format with new password missing username' => [
                [
                    'challenge_name' => 'NEW_PASSWORD_REQUIRED',
                    'password' => 'ValidPassword!234',
                    'new_password' => 'NewValidPassword!234'
                ],
                ValidationException::class,
            ],
            'valid data format with all required fields' => [
                [
                    'challenge_name' => 'NEW_PASSWORD_REQUIRED',
                    'password' => 'ValidPassword!345',
                    'new_password' => 'NewValidPassword!345',
                    'new_password_confirmation' => 'NewValidPassword!345',
                    'email' => 'someone@example.com'
                ],
                AwsCognitoException::class,
            ],
        ];
    } // Function ends
} //Class ends
