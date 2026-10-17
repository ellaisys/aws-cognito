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
use Ellaisys\Cognito\Traits\ManagesUserPoolAction;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('trait'), Group('userpool')]
#[CoversTrait(ManagesUserPoolAction::class)]
class ManagesUserPoolActionTest extends TestCase
{
    private $client;

    const SAMPLE_INVALID_DATA = [
        'pool_id' => 'invalid_pool_id',
        'client_id' => 'invalid_client_id',
        'pagination_token' => 'invalid_pagination_token'
    ];

    const SAMPLE_DATA = [
        'name' => 'new_pool_name',
        'description' => 'sample_description',
    ];

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        //Create AWS Cognito Client
        $this->client = app()->make(AwsCognitoClient::class);
    } //Function ends

    /**
     * Test if the listUserPools method exists.
     */
    #[Test]
    public function test_method_listUserPools_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'listUserPools'));
    } //Function ends

    /**
     * Test the response of the listUserPools method with valid parameters.
     */
    #[Test]
    #[Depends('test_method_listUserPools_exists')]
    public function test_listUserPools_method_valid_response(): void
    {
        $response = $this->client->listUserPools();
        $this->assertIsArray($response->toArray());
    } //Function ends

    /**
     * Test the response of the listUserPools method with invalid parameters.
     */
    #[Test]
    #[Depends('test_method_listUserPools_exists')]
    public function test_listUserPools_method_invalid_response(): void
    {
        $this->expectException(HttpException::class);
        $response = $this->client->listUserPools(
            10, self::SAMPLE_INVALID_DATA['pagination_token']
        );
    } //Function ends

    /**
     * Test if the describeUserPool method exists.
     */
    #[Test]
    public function test_method_describeUserPool_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'describeUserPool'));
    } //Function ends

    /**
     * Test the response of the describeUserPool method with valid data.
     */
    #[Test]
    #[Depends('test_method_describeUserPool_exists')]
    public function test_describeUserPool_method_with_valid_data(): void
    {
        $response = $this->client->describeUserPool();
        $this->assertIsArray($response->toArray());
    } //Function ends

    /**
     * Test the response of the describeUserPool method with invalid data.
     */
    #[Test]
    #[Depends('test_method_describeUserPool_exists')]
    public function test_describeUserPool_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->describeUserPool(self::SAMPLE_INVALID_DATA['pool_id']);
    } //Function ends

    /**
     * Test if the createUserPool method exists.
     */
    #[Test]
    public function test_method_createUserPool_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'createUserPool'));
    } //Function ends

    /**
     * Test the response of the createUserPool method with valid data.
     */
    #[Test]
    #[Depends('test_method_createUserPool_exists')]
    public function test_createUserPool_method_with_valid_data(): string
    {
        $response = $this->client->createUserPool(
            self::SAMPLE_DATA['name']
        );
        $data = $response->toArray();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('UserPool', $data);
        $this->assertArrayHasKey('Id', $data['UserPool']);

        return $data['UserPool']['Id'];
    } //Function ends

    /**
     * Test the response of the createUserPool method with invalid data.
     */
    #[Test]
    #[Depends('test_method_createUserPool_exists')]
    public function test_createUserPool_method_with_invalid_data(): void
    {
        // Set configuration at runtime
        Config::set('cognito.mfa_setup', 'OPTIONAL');
        Config::set('cognito.signin_policy', ['SMS_OTP']);
        Config::set('cognito.user_pool_device_enabled', true);

        $this->expectException(Exception::class);
        $this->client->createUserPool('some_pool_name');
    } //Function ends

    /**
     * Test if the updateUserPool method exists.
     */
    #[Test]
    public function test_method_updateUserPool_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'updateUserPool'));
    } //Function ends

    /**
     * Test the response of the updateUserPool method with valid data.
     */
    #[Test]
    #[Depends('test_createUserPool_method_with_valid_data')]
    public function test_updateUserPool_method_with_valid_data(string $userPoolId): string
    {
        $response = $this->client->updateUserPool(
            $userPoolId
        );
        $this->assertIsArray($response->toArray());

        return $userPoolId;
    } //Function ends

    /**
     * Test the response of the updateUserPool method with invalid data.
     */
    #[Test]
    #[Depends('test_method_updateUserPool_exists')]
    public function test_updateUserPool_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->updateUserPool(
            self::SAMPLE_INVALID_DATA['pool_id'],
            ['name' => 'Invalid Pool Name']
        );
    } //Function ends

    /**
     * Test if the deleteUserPool method exists.
     */
    #[Test]
    public function test_method_deleteUserPool_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'deleteUserPool'));
    } //Function ends

    /**
     * Test the response of the deleteUserPool method with invalid data.
     */
    #[Test]
    #[Depends('test_method_deleteUserPool_exists')]
    public function test_deleteUserPool_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->deleteUserPool(self::SAMPLE_INVALID_DATA['pool_id']);
    } //Function ends

    /**
     * Test the response of the deleteUserPool method with valid data.
     */
    #[Test]
    #[Depends('test_updateUserPool_method_with_valid_data')]
    public function test_deleteUserPool_method_with_valid_data(string $userPoolId): void
    {
        $response = $this->client->deleteUserPool($userPoolId);
        $this->assertIsArray($response->toArray());
    } //Function ends

    /**
     * Test if the listUserPoolClients method exists.
     */
    #[Test]
    public function test_method_listUserPoolClients_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'listUserPoolClients'));
    } //Function ends

    /**
     * Test the response of the listUserPoolClients method with valid parameters.
     */
    #[Test]
    #[Depends('test_method_listUserPoolClients_exists')]
    public function test_listUserPoolClients_method_valid_response(): void
    {
        $response = $this->client->listUserPoolClients();
        $this->assertIsArray($response->toArray());
    } //Function ends

    /**
     * Test the response of the listUserPoolClients method with invalid parameters.
     */
    #[Test]
    #[Depends('test_method_listUserPoolClients_exists')]
    public function test_listUserPoolClients_method_invalid_response(): void
    {
        $this->expectException(HttpException::class);
        $response = $this->client->listUserPoolClients(
            10, self::SAMPLE_INVALID_DATA['pagination_token'],
            self::SAMPLE_INVALID_DATA['pool_id']
        );
    } //Function ends

    /**
     * Test if the describeUserPoolClient method exists.
     */
    #[Test]
    public function test_method_describeUserPoolClient_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'describeUserPoolClient'));
    } //Function ends

    /**
     * Test the response of the describeUserPoolClient method with valid data.
     */
    #[Test]
    #[Depends('test_method_describeUserPoolClient_exists')]
    public function test_describeUserPoolClient_method_with_valid_data(): void
    {
        $response = $this->client->describeUserPoolClient();
        $this->assertIsArray($response->toArray());
    } //Function ends

    /**
     * Test the response of the describeUserPoolClient method with invalid data.
     */
    #[Test]
    #[Depends('test_method_describeUserPoolClient_exists')]
    public function test_describeUserPoolClient_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->describeUserPoolClient(
            self::SAMPLE_INVALID_DATA['pool_id'],
            self::SAMPLE_INVALID_DATA['client_id']
        );
    } //Function ends

    /**
     * Test if the createUserPoolClient method exists.
     */
    #[Test]
    public function test_method_createUserPoolClient_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'createUserPoolClient'));
    } //Function ends

    /**
     * Test the response of the createUserPoolClient method with valid data.
     */
    #[Test]
    #[Depends('test_method_createUserPoolClient_exists')]
    public function test_createUserPoolClient_method_with_valid_data(): array
    {
        $response = $this->client->createUserPoolClient(
            self::SAMPLE_DATA['name'] . 'Client'
        );
        $data = $response->toArray();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('UserPoolClient', $data);
        $this->assertArrayHasKey('UserPoolId', $data['UserPoolClient']);
        $this->assertArrayHasKey('ClientId', $data['UserPoolClient']);

        return $data['UserPoolClient'];
    } //Function ends

    /**
     * Test the response of the createUserPoolClient method with invalid data.
     */
    #[Test]
    #[Depends('test_method_createUserPoolClient_exists')]
    public function test_createUserPoolClient_method_with_invalid_data(): void
    {
        $this->expectException(Exception::class);
        $this->client->createUserPoolClient(
            self::SAMPLE_INVALID_DATA['client_id'],
            self::SAMPLE_INVALID_DATA['pool_id']
        );
    } //Function ends

    /**
     * Test if the deleteUserPoolClient method exists.
     */
    #[Test]
    public function test_method_deleteUserPoolClient_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'deleteUserPoolClient'));
    } //Function ends

    /**
     * Test the response of the deleteUserPoolClient method with invalid data.
     */
    #[Test]
    #[Depends('test_method_deleteUserPoolClient_exists')]
    public function test_deleteUserPoolClient_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->deleteUserPoolClient(
            self::SAMPLE_INVALID_DATA['pool_id'],
            self::SAMPLE_INVALID_DATA['client_id']
        );
    } //Function ends

    /**
     * Test the response of the deleteUserPoolClient method with valid data.
     */
    #[Test]
    #[Depends('test_createUserPoolClient_method_with_valid_data')]
    public function test_deleteUserPoolClient_method_with_valid_data(
        array $data): void
    {
        $response = $this->client->deleteUserPoolClient(
            $data['UserPoolId'],
            $data['ClientId']
        );
        $this->assertIsArray($response->toArray());
    } //Function ends

} // Class ends
