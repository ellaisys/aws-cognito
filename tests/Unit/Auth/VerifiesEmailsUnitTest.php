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
use Ellaisys\Cognito\Auth\VerifiesEmails;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('auth-trait'), Group('verify')]
#[CoversTrait(VerifiesEmails::class)]
class VerifiesEmailsUnitTest extends TestCase
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
            use VerifiesEmails;
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
     * Test that the class is an instance of the anonymous class using VerifiesEmails.
     */
    #[Test]
    public function test_class_is_instance_of_anonymous_class(): void
    {
        $this->assertInstanceOf(get_class($this->class), $this->class);
    } // Function ends

    /**
     * Test that the resend method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_verify_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'verify'),
            'Method verify does not exist in the class using VerifiesEmails.'
        );
    } // Function ends

    /**
     * Test the verify method with invalid data.
     *
     * @dataProvider verifyMethodExceptionDataProvider
     */
    #[Test]
    #[Depends('test_method_verify_exists_in_class')]
    #[DataProvider('verifyMethodExceptionDataProvider')]
    public function test_method_verify_with_invalid_data(array $data,
        string $expectedException): void
    {
        $request = $this->requestJson;
        $request->merge($data);
        $this->expectException($expectedException);
        $this->class->verify($request);
    } // Function ends

    /**
     * Data provider for test_method_verify_with_invalid_data.
     *
     * @return array
     */
    public static function verifyMethodExceptionDataProvider(): array
    {
        $someEmail = 'someone@example.com';

        return [
            'no data' => [
                [],
                ValidationException::class,
            ],
            'empty email' => [
                ['email' => ''],
                ValidationException::class,
            ],
            'invalid email' => [
                ['email' => 'invalid-email'],
                ValidationException::class,
            ],
            'missing email' => [
                ['email' => null],
                ValidationException::class,
            ],
            'valid email but missing code' => [
                ['email' => $someEmail, 'code' => null],
                ValidationException::class,
            ],
            'valid email but empty code' => [
                ['email' => $someEmail, 'code' => ''],
                ValidationException::class,
            ],
            'missing both code and email' => [
                ['email' => null, 'code' => null],
                ValidationException::class,
            ],
            'code non numeric' => [
                ['email' => $someEmail, 'code' => 'abc'],
                ValidationException::class,
            ],
            'valid payload format' => [
                ['email' => 'someone@gmail.com', 'code' => '123456'],
                AwsCognitoException::class,
            ],
        ];
    } // Function ends

    /**
     * Test method verify with query data and invalid payload.
     */
    #[Test]
    #[Depends('test_method_verify_exists_in_class')]
    public function test_method_verify_with_query_data_invalid_payload(): void
    {
        $request = Request::create('/?email=someone@example.com', 'POST', [], [], [],
            [
                'HTTP_ACCEPT' => TestCase::APPLICATION_JSON,
                'CONTENT_TYPE' => TestCase::APPLICATION_JSON,
            ]
        );
        $request->merge(['code' => 'abcd']);
        $this->expectException(ValidationException::class);
        $this->class->verify($request);
    } // Function ends

    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_resend_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'resend'),
            'Method resend does not exist in the class using VerifiesEmails.'
        );
    } // Function ends

    /**
     * Test method resend with invalid data.
     */
    #[Test]
    #[Depends('test_method_resend_exists_in_class')]
    public function test_method_resend_with_invalid_data(): void
    {
        $this->expectException(ValidationException::class);
        $this->class->resend($this->requestJson);
    } // Function ends

    /**
     * Test method resend with query data and invalid payload.
     */
    #[Test]
    #[Depends('test_method_resend_exists_in_class')]
    #[DataProvider('resendMethodExceptionDataProvider')]
    public function test_method_resend_with_query_data_invalid_payload(
        string $key, ?string $value = null,
        ?string $expectedException = ValidationException::class): void
    {
        $request = Request::create('/?' . $key . '=' . $value ?: '', 'POST', [], [], [],
            [
                'HTTP_ACCEPT' => TestCase::APPLICATION_JSON,
                'CONTENT_TYPE' => TestCase::APPLICATION_JSON,
            ]
        );
        $this->expectException($expectedException);
        $this->class->resend($request);
    } // Function ends

    /**
     * Data provider for test_method_resend_with_query_data_invalid_payload.
     */
    public static function resendMethodExceptionDataProvider(): array
    {
        return [
            'missing email' => [
                'email', null,
                ValidationException::class,
            ],
            'wrong key' => [
                'some_key', 'some_value',
                ValidationException::class,
            ],
        ];
    } // Function ends

    /**
     * Test method resend with valid payload.
     */
    #[Test]
    #[Depends('test_method_resend_exists_in_class')]
    public function test_method_resend_with_valid_payload(): void
    {
        $request = Request::create('/?email=someone@gmail.com', 'POST', [], [], [],
            [
                'HTTP_ACCEPT' => TestCase::APPLICATION_JSON,
                'CONTENT_TYPE' => TestCase::APPLICATION_JSON,
            ]
        );
        $this->class->isControllerAction = true;
        $response = $this->class->resend($request);
        $this->assertNotNull($response);
        $this->assertIsArray($response->toArray());
    } // Function ends
} //Class ends
