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
use Ellaisys\Cognito\Auth\RegisterMFA;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('auth-trait'), Group('mfa'), Group('register-mfa')]
#[CoversTrait(RegisterMFA::class)]
class RegisterMFAUnitTest extends TestCase
{
    private $class;
    private Request $requestJson;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        // Set config values
        Config::set('cognito.mfa_setup', 'OPTIONAL');

        // Create a new fixture
        $this->class = new class {
            use RegisterMFA;
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
     * Test that the class is an instance of the anonymous class using RegisterMFA.
     */
    #[Test]
    public function test_class_is_instance_of_anonymous_class(): void
    {
        $this->assertInstanceOf(get_class($this->class), $this->class);
    } // Function ends

    /**
     * Test that the activate method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_activate_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'activate'),
            'Method activate does not exist in the class using RegisterMFA.'
        );
    } // Function ends

    #[Test]
    #[Depends('test_method_activate_exists_in_class')]
    public function test_activate_method_basic_call_but_unauthenticated_user(): void
    {
        $this->expectException(HttpException::class);
        $this->class->activate(request());
    } // Function ends

    /**
     * Test that the verify method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_verify_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'verify'),
            'Method verify does not exist in the class using RegisterMFA.'
        );
    } // Function ends

    /**
     * Test that the verify method can be called but with an unauthenticated user.
     */
    #[Test]
    #[Depends('test_method_verify_exists_in_class')]
    #[DataProvider('provideVerifyMethodBasicCallData')]
    public function test_verify_method_basic_call_but_unauthenticated_user(
        array $data, string $expectedException, string $code=null,
        string $deviceName=null,
    ): void
    {
        $this->expectException($expectedException);
        $this->class->verify(request()->merge($data), $code, $deviceName);
    } // Function ends

    /**
     * Data provider for test_verify_method_basic_call_but_unauthenticated_user.
     */
    public static function provideVerifyMethodBasicCallData(): array
    {
        return [
            'no data' => [
                [],
                ValidationException::class,
            ],
            'malformed code' => [
                ['code' => true],
                ValidationException::class,
            ],
            'empty code' => [
                ['code' => null],
                ValidationException::class,
            ],
            'non-string code' => [
                ['code' => 123456],
                ValidationException::class,
            ],
            'valid code' => [
                ['code' => '123456', 'device_name' => 'test_device'],
                HttpException::class,
            ],
            'valid code but no device name' => [
                ['code' => '123456'],
                HttpException::class,
            ],
            'valid code and device name as arguments' => [
                [],
                HttpException::class,
                '123456',
                'test_device',
            ],
        ];
    } // Function ends

    /**
     * Test that the deactivate method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_deactivate_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'deactivate'),
            'Method deactivate does not exist in the class using RegisterMFA.'
        );
    } // Function ends

    /**
     * Test that the deactivate method throws an HttpException for unauthenticated users.
     */
    #[Test]
    #[Depends('test_method_deactivate_exists_in_class')]
    public function test_deactivate_method_basic_call_but_unauthenticated_user(): void
    {
        $this->expectException(HttpException::class);
        $this->class->deactivate(request());
    } // Function ends

    /**
     * Test that the enable method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_enable_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'enable'),
            'Method enable does not exist in the class using RegisterMFA.'
        );
    } // Function ends

    /**
     * Test that the enable method throws an HttpException for unauthenticated users.
     */
    #[Test]
    #[Depends('test_method_enable_exists_in_class')]
    public function test_enable_method_basic_call_but_unauthenticated_user(): void
    {
        $this->expectException(HttpException::class);
        $this->class->enable(request());
    } // Function ends

    /**
     * Test that the disable method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_disable_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'disable'),
            'Method disable does not exist in the class using RegisterMFA.'
        );
    } // Function ends

    /**
     * Test that the disable method throws an HttpException for unauthenticated users.
     */
    #[Test]
    #[Depends('test_method_disable_exists_in_class')]
    public function test_disable_method_basic_call_but_unauthenticated_user(): void
    {
        $this->expectException(HttpException::class);
        $this->class->disable(request());
    } // Function ends

} //Class ends
