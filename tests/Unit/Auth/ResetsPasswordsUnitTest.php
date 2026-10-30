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

use Illuminate\Support\ViewErrorBag;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversTrait;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\EncryptionTypes;
use Ellaisys\Cognito\Auth\ResetsPasswords;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('auth-trait'), Group('password')]
#[CoversTrait(ResetsPasswords::class)]
class ResetsPasswordsUnitTest extends TestCase
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
            use ResetsPasswords;
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
     * Test that the class is an instance of the anonymous class using ResetsPasswords.
     */
    #[Test]
    public function test_class_is_instance_of_anonymous_class(): void
    {
        $this->assertInstanceOf(get_class($this->class), $this->class);
    } // Function ends

    /**
     * Test that the reset method exists in the class using ResetsPasswords trait.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_reset_exists(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'reset'),
            'Method reset does not exist in the class using ResetsPasswords trait'
        );
    } // Function ends

    /**
     * Test that the reset method throws an exception when called with the JSON request.
     */
    #[Test]
    #[Depends('test_method_reset_exists')]
    #[DataProvider('resetMethodExceptionDataProvider')]
    public function test_method_reset_with_invalid_data(array $data,
        string $expectedException): void
    {
        $request = $this->requestJson;
        $request->merge($data);

        $this->expectException($expectedException);
        $this->class->reset($request);
    } // Function ends

    /**
     * Data provider for test_method_reset_with_invalid_data.
     *
     * @return array
     */
    public static function resetMethodExceptionDataProvider(): array
    {
        return [
            'empty request' => [
                [],
                ValidationException::class
            ],
            'invalid data format' => [
                ['token' => 'abcd'],
                ValidationException::class
            ],
            'valid data' => [
                [
                    'token' => 'valid_token',
                    'email' => 'valid_email@example.com',
                    'password' => 'ValidPassword!123',
                    'password_confirmation' => 'ValidPassword!123',
                ],
                AwsCognitoException::class
            ]
        ];
    } // Function ends


    /**
     * Test that the showResetForm method exists in the class using
     * ResetsPasswords trait.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_showResetForm_exists(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'showResetForm'),
            'Method showResetForm does not exist in the class using ResetsPasswords trait'
        );
    } // Function ends

    /**
     * Test that the showResetForm method returns the correct view with the
     * given email and token.
     */
    #[Test]
    #[Depends('test_method_showResetForm_exists')]
    #[DataProvider('showResetFormDataProvider')]
    public function test_show_reset_form_view(array $data, string $token): void
    {
        // Share an empty errors bag so the view doesn't fail with missing errors variable
        View::share('errors', new ViewErrorBag);

        $request = request();
        $request->merge($data);
        $view = $this->class->showResetForm($request, $token);

        $rendered = $view->render();

        $this->assertStringContainsString($data['email'], $rendered);
        $this->assertStringContainsString($token, $rendered);
    } // Function ends

    /**
     * Data provider for test_show_reset_form_view.
     *
     * @return array
     */
    public static function showResetFormDataProvider(): array
    {
        return [
            'with email and token' => [
                ['email' => 'test@example.com'],
                'test_token'
            ]
        ];
    } // Function ends
} //Class ends
