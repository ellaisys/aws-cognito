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
use Ellaisys\Cognito\Auth\DeviceActions;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('auth-trait'), Group('device')]
#[CoversTrait(DeviceActions::class)]
class DeviceActionsUnitTest extends TestCase
{
    private $class;
    private Request $requestJson;

    const SAMPLE_DATA = [
        'device_key' => 'sample_device_key',
        'device_name' => 'sample_device_name',
    ];

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
            use DeviceActions;
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
     * Test that the class is an instance of the anonymous class using DeviceActions.
     */
    #[Test]
    public function test_class_is_instance_of_anonymous_class(): void
    {
        $this->assertInstanceOf(get_class($this->class), $this->class);
    } // Function ends

    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_list_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'list'),
            'Method list does not exist in the class using DeviceActions.'
        );
    } // Function ends

    /**
     * Test that the list method throws an HttpException when provided with
     * invalid data.
     */
    #[Test]
    #[Depends('test_method_list_exists_in_class')]
    public function test_method_list_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->class->list($this->requestJson);
    } // Function ends

    /**
     * Test that the create method exists in the class using DeviceActions.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_create_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'create'),
            'Method create does not exist in the class using DeviceActions.'
        );
    } // Function ends

    /**
     * Test that the create method throws an ValidationException when provided
     * with invalid data.
     */
    #[Test]
    #[Depends('test_method_create_exists_in_class')]
    public function test_method_create_with_invalid_data(): void
    {
        $this->expectException(ValidationException::class);
        $this->class->create($this->requestJson);
    } // Function ends

    /**
     * Test that the create method throws an HttpException when provided
     * with valid data format but an unauthenticated user.
     */
    #[Test]
    #[Depends('test_method_create_exists_in_class')]
    public function test_method_create_with_valid_data_format_but_unauthenticated_user(): void
    {
        $request = $this->requestJson;
        $request->merge(self::SAMPLE_DATA);

        $this->expectException(HttpException::class);
        $this->class->create($request);
    } // Function ends

    /**
     * Test that the update method exists in the class using DeviceActions.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_update_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'update'),
            'Method update does not exist in the class using DeviceActions.'
        );
    } // Function ends

    /**
     * Test that the update method throws an ValidationException when provided
     * with invalid data.
     */
    #[Test]
    #[Depends('test_method_update_exists_in_class')]
    public function test_method_update_with_invalid_data(): void
    {
        $this->expectException(ValidationException::class);
        $this->class->update($this->requestJson);
    } // Function ends

    /**
     * Test that the update method throws an HttpException when provided
     * with valid data format but an unauthenticated user.
     */
    #[Test]
    #[Depends('test_method_update_exists_in_class')]
    public function test_method_update_with_valid_data_format_but_unauthenticated_user(): void
    {
        $this->expectException(HttpException::class);
        $this->class->update($this->requestJson, self::SAMPLE_DATA['device_key']);
    } // Function ends

    /**
     * Test that the challenge method exists in the class using DeviceActions.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_challenge_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'challenge'),
            'Method challenge does not exist in the class using DeviceActions.'
        );
    } // Function ends

    /**
     * Test that the challenge method throws an HttpException when provided with
     * invalid data.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_challenge_with_invalid_data(): void
    {
        $this->expectException(ValidationException::class);
        $this->class->challenge($this->requestJson, 'sample_challenge_name');
    } // Function ends

    /**
     * Test that the delete method exists in the class using DeviceActions.
     */
    #[Test]
    #[Depends('test_class_is_instance_of_anonymous_class')]
    public function test_method_delete_exists_in_class(): void
    {
        $this->assertTrue(
            method_exists($this->class, 'delete'),
            'Method delete does not exist in the class using DeviceActions.'
        );
    } // Function ends

    /**
     * Test that the delete method throws an ValidationException when provided with
     * invalid data.
     */
    #[Test]
    #[Depends('test_method_delete_exists_in_class')]
    public function test_method_delete_with_invalid_data(): void
    {
        $this->expectException(ValidationException::class);
        $this->class->delete($this->requestJson);
    } // Function ends

    /**
     * Test that the delete method throws an HttpException when provided with
     * valid data for an unauthenticated user.
     */
    #[Test]
    #[Depends('test_method_delete_exists_in_class')]
    public function test_method_delete_with_valid_data_unauthenticated_user(): void
    {
        $this->expectException(HttpException::class);
        $this->class->delete($this->requestJson, self::SAMPLE_DATA['device_key']);
    } // Function ends
} //Class ends
