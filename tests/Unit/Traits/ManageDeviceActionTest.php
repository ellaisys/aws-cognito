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
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\EncryptionTypes;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Traits\ManageDeviceAction;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('trait'), Group('device')]
#[CoversTrait(ManageDeviceAction::class)]
class ManageDeviceActionTest extends TestCase
{
    private $client;

    const SAMPLE_DATA = [
        'device_key' => 'sample_device_key',
        'device_name' => 'sample_device_name',
    ];

    const SAMPLE_INVALID_DATA = [
        'username' => 'invalid_username',
        'access_token' => 'invalid_access_token',
        'device_key' => 'invalid_device_key',
        'device_name' => 'invalid_device_name',
        'pagination_token' => 'invalid_pagination_token'
    ];

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        //Create AWS Cognito Client
        $this->client = app()->make(AwsCognitoClient::class);
    } // Function ends

    /**
     * Test if the listDevices method exists.
     */
    #[Test]
    public function test_method_listDevices_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'listDevices'));
    } // Function ends

    /**
     * Test the response of the listDevices method with invalid data.
     */
    #[Test]
    #[Depends('test_method_listDevices_exists')]
    public function test_listDevices_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->listDevices(
            self::SAMPLE_INVALID_DATA['access_token'],
            10,
            self::SAMPLE_INVALID_DATA['pagination_token']
        );
    } // Function ends

    /**
     * Test if the getDevice method exists.
     */
    #[Test]
    public function test_method_getDevice_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'getDevice'));
    } // Function ends

    /**
     * Test the response of the getDevice method with invalid data.
     */
    #[Test]
    #[Depends('test_method_getDevice_exists')]
    public function test_getDevice_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->getDevice(
            self::SAMPLE_INVALID_DATA['access_token'],
            self::SAMPLE_INVALID_DATA['device_key']
        );
    } // Function ends

    /**
     * Test if the confirmDevice method exists.
     */
    #[Test]
    public function test_method_confirmDevice_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'confirmDevice'));
    } // Function ends

    /**
     * Test the response of the confirmDevice method with invalid data.
     */
    #[Test]
    #[Depends('test_method_confirmDevice_exists')]
    public function test_confirmDevice_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->confirmDevice(
            self::SAMPLE_INVALID_DATA['access_token'],
            self::SAMPLE_INVALID_DATA['device_key'],
            self::SAMPLE_INVALID_DATA['device_name'],
            []
        );
    } // Function ends

    /**
     * Test if the updateDeviceStatus method exists.
     */
    #[Test]
    public function test_method_updateDeviceStatus_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'updateDeviceStatus'));
    } // Function ends

    /**
     * Test the response of the updateDeviceStatus method with invalid data.
     */
    #[Test]
    #[Depends('test_method_updateDeviceStatus_exists')]
    public function test_updateDeviceStatus_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->updateDeviceStatus(
            self::SAMPLE_INVALID_DATA['access_token'],
            self::SAMPLE_INVALID_DATA['device_key']
        );
    } // Function ends

    /**
     * Test if the forgetDevice method exists.
     */
    #[Test]
    public function test_method_forgetDevice_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'forgetDevice'));
    } // Function ends

    /**
     * Test the response of the forgetDevice method with invalid data.
     */
    #[Test]
    #[Depends('test_method_forgetDevice_exists')]
    public function test_forgetDevice_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->forgetDevice(
            self::SAMPLE_INVALID_DATA['access_token'],
            self::SAMPLE_INVALID_DATA['device_key']
        );
    } // Function ends


    /**
     * Test if the adminListDevices method exists.
     */
    #[Test]
    public function test_method_adminListDevices_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminListDevices'));
    } // Function ends

    /**
     * Test the response of the adminListDevices method with invalid data.
     */
    #[Test]
    #[Depends('test_method_adminListDevices_exists')]
    public function test_adminListDevices_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminListDevices(
            self::SAMPLE_INVALID_DATA['username'],
            self::SAMPLE_INVALID_DATA['pagination_token']
        );
    } // Function ends

    /**
     * Test if the adminGetDevice method exists.
     */
    #[Test]
    public function test_method_adminGetDevice_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminGetDevice'));
    } // Function ends

    /**
     * Test the response of the adminGetDevice method with invalid data.
     */
    #[Test]
    #[Depends('test_method_adminGetDevice_exists')]
    public function test_adminGetDevice_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminGetDevice(
            self::SAMPLE_INVALID_DATA['username'],
            self::SAMPLE_INVALID_DATA['device_key']
        );
    } // Function ends

    /**
     * Test if the adminForgetDevice method exists.
     */
    #[Test]
    public function test_method_adminForgetDevice_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminForgetDevice'));
    } // Function ends

    /**
     * Test the response of the adminForgetDevice method with invalid data.
     */
    #[Test]
    #[Depends('test_method_adminForgetDevice_exists')]
    public function test_adminForgetDevice_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminForgetDevice(
            self::SAMPLE_INVALID_DATA['username'],
            self::SAMPLE_INVALID_DATA['device_key']
        );
    } // Function ends

    /**
     * Test if the adminUpdateDeviceStatus method exists.
     */
    #[Test]
    public function test_method_adminUpdateDeviceStatus_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminUpdateDeviceStatus'));
    } // Function ends

    /**
     * Test the response of the adminUpdateDeviceStatus method with invalid data.
     */
    #[Test]
    #[Depends('test_method_adminUpdateDeviceStatus_exists')]
    public function test_adminUpdateDeviceStatus_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminUpdateDeviceStatus(
            self::SAMPLE_INVALID_DATA['username'],
            self::SAMPLE_INVALID_DATA['device_key']
        );
    } // Function ends
} // Class ends
