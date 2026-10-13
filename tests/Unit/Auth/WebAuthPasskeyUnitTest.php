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
use Ellaisys\Cognito\Auth\WebAuthPasskey;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('auth-trait'), Group('web-authn-passkey')]
#[CoversTrait(WebAuthPasskey::class)]
class WebAuthPasskeyUnitTest extends TestCase
{
    private $class;
    private Request $requestJson;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        // Create a new fixture
        $this->class = new class {
            use WebAuthPasskey;
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
     * Test that the class is an instance of the anonymous class using WebAuthPasskey.
     */
    #[Test]
    public function test_class_is_instance_of_anonymous_class(): void
    {
        $this->assertInstanceOf(get_class($this->class), $this->class);
    } // Function ends

    /**
     * Test that the start method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_start_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'start'),
            'Method start does not exist in the class using WebAuthPasskey.'
        );
    } // Function ends

    #[Test]
    #[Depends('test_method_start_exists_in_class')]
    public function test_method_start_with_no_data_with_authenticated_user(): void
    {
        $this->expectException(HttpException::class);
        $this->class->start(request());
    } // Function ends

    /**
     * Test that the complete method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_complete_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'complete'),
            'Method complete does not exist in the class using WebAuthPasskey.'
        );
    } // Function ends

    /**
     * Test that the complete method throws an HttpException for unauthenticated users.
     */
    #[Test]
    #[Depends('test_method_complete_exists_in_class')]
    public function test_complete_method_no_data_with_authenticated_user(): void
    {
        $this->expectException(ValidationException::class);
        $this->class->complete(request());
    } // Function ends

    /**
     * Test that the complete method throws an HttpException for unauthenticated users.
     */
    #[Test]
    #[Depends('test_method_complete_exists_in_class')]
    public function test_complete_method_valid_data_with_authenticated_user(): void
    {
        $this->expectException(HttpException::class);
        $this->class->complete(request()->merge(['credential' => 'data']));
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
            'Method challenge does not exist in the class using WebAuthPasskey.'
        );
    } // Function ends

    /**
     * Test that the challenge method throws an HttpException for unauthenticated users.
     */
    #[Test]
    #[Depends('test_method_challenge_exists_in_class')]
    #[DataProvider('challengeMethodDataProvider')]
    public function test_challenge_method_no_data_with_authenticated_user(
        array $data, string $expectedException, string $challengeName = null): void
    {
        $this->expectException($expectedException);
        $this->class->challenge(request()->merge($data), $challengeName);
    } // Function ends

    /**
     * Data provider for the test_challenge_method_no_data_with_authenticated_user test.
     */
    public static function challengeMethodDataProvider(): array
    {
        return [
            [[], ValidationException::class],
            [['challenge_name' => 'some_challenge'], ValidationException::class],
            [['challenge_name' => 'some_challenge'], ValidationException::class, 'WEB_AUTHN'],
            [['challenge_name' => 'WEB_AUTHN', 'extra_data' => 'value'], ValidationException::class],
        ];
    } // Function ends

    /**
     * Test that the delete method exists in the class.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_delete_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'delete'),
            'Method delete does not exist in the class using WebAuthPasskey.'
        );
    } // Function ends

    /**
     * Test that the delete method throws an HttpException for unauthenticated users.
     */
    #[Test]
    #[Depends('test_method_delete_exists_in_class')]
    #[DataProvider('deleteMethodDataProvider')]
    public function test_delete_method_for_authenticated_user(
        array $data, string $expectedException): void
    {
        $this->expectException($expectedException);
        $this->class->delete(request()->merge($data));
    } // Function ends

    /**
     * Data provider for the test_delete_method_for_authenticated_user test.
     */
    public static function deleteMethodDataProvider(): array
    {
        return [
            [[], ValidationException::class],
            [['credential_id' => 'some_invalid_id'], HttpException::class],
        ];
    } // Function ends
} //Class ends
