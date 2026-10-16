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
use Illuminate\Http\Request;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversTrait;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\EncryptionTypes;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Traits\ManageGroupAction;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('trait'), Group('group')]
#[CoversTrait(ManageGroupAction::class)]
class ManageGroupActionTest extends TestCase
{
    private $client;
    private Request $requestJson;

    const SAMPLE_DATA = [
        'group_name' => 'new_group_name',
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
     * Test if the listGroups method exists.
     */
    #[Test]
    public function test_method_listGroups_exits(): void
    {
        $this->assertTrue(method_exists($this->client, 'listGroups'));
    } //Function ends

    /**
     * Test the response of the listGroups method with valid parameters.
     */
    #[Test]
    #[Depends('test_method_listGroups_exits')]
    public function test_listGroups_method_valid_response(): void
    {
        $response = $this->client->listGroups();
        $this->assertIsArray($response->toArray());
    } //Function ends

    /**
     * Test the response of the listGroups method with invalid parameters.
     */
    #[Test]
    #[Depends('test_method_listGroups_exits')]
    public function test_listGroups_method_invalid_response(): void
    {
        $this->expectException(HttpException::class);
        $response = $this->client->listGroups(10, 'some_token', 'invalid_parameter');
    } //Function ends

    /**
     * Test if the getGroup method exists.
     */
    #[Test]
    public function test_method_getGroup_exits(): void
    {
        $this->assertTrue(method_exists($this->client, 'getGroup'));
    } //Function ends

    /**
     * Test the response of the getGroup method with a valid group name.
     */
    #[Test]
    #[Depends('test_method_getGroup_exits')]
    public function test_getGroup_method_with_valid_group_name(): void
    {
        $response = $this->client->getGroup('default');
        $this->assertIsArray($response->toArray());
    } //Function ends

    /**
     * Test the response of the getGroup method with an invalid group name.
     */
    #[Test]
    #[Depends('test_method_getGroup_exits')]
    public function test_getGroup_method_with_invalid_group_name(): void
    {
        $this->expectException(HttpException::class);
        $this->client->getGroup('invalid_group_name');
    } //Function ends

    /**
     * Test if the createGroup method exists.
     */
    #[Test]
    public function test_method_createGroup_exits(): void
    {
        $this->assertTrue(method_exists($this->client, 'createGroup'));
    } //Function ends

    /**
     * Test the response of the createGroup method with a valid group name.
     */
    #[Test]
    #[Depends('test_method_createGroup_exits')]
    public function test_createGroup_method_with_valid_group_name(): void
    {
        $response = $this->client->createGroup(
            self::SAMPLE_DATA['group_name']
        );
        $this->assertIsArray($response->toArray());
    } //Function ends

    /**
     * Test the response of the createGroup method with an invalid group name.
     */
    #[Test]
    #[Depends('test_method_createGroup_exits')]
    public function test_createGroup_method_with_invalid_group_name(): void
    {
        $this->expectException(HttpException::class);
        $this->client->createGroup(
            self::SAMPLE_DATA['group_name'], 'some description',
            0, 'some_string_of_20_characters', 'wrong_pool_id');
    } //Function ends

    /**
     * Test if the updateGroup method exists.
     */
    #[Test]
    public function test_method_updateGroup_exits(): void
    {
        $this->assertTrue(method_exists($this->client, 'updateGroup'));
    } //Function ends

    /**
     * Test the response of the updateGroup method with a valid group name.
     */
    #[Test]
    #[Depends('test_method_updateGroup_exits')]
    public function test_updateGroup_method_with_valid_group_name(): void
    {
        $response = $this->client->updateGroup('default', 'Default Group');
        $this->assertIsArray($response->toArray());
    } //Function ends

    /**
     * Test the response of the updateGroup method with an invalid group name.
     */
    #[Test]
    #[Depends('test_method_updateGroup_exits')]
    public function test_updateGroup_method_with_invalid_group_name(): void
    {
        $this->expectException(HttpException::class);
        $this->client->updateGroup(
            'some_invalid_group_name', 'some description',
            0, 'some_string_of_20_characters');
    } //Function ends

    /**
     * Test if the deleteGroup method exists.
     */
    #[Test]
    public function test_method_deleteGroup_exits(): void
    {
        $this->assertTrue(method_exists($this->client, 'deleteGroup'));
    } //Function ends

    /**
     * Test the response of the deleteGroup method with an invalid group name.
     */
    #[Test]
    #[Depends('test_method_deleteGroup_exits')]
    public function test_deleteGroup_method_with_invalid_group_name(): void
    {
        $this->expectException(HttpException::class);
        $this->client->deleteGroup('some_other_invalid_group_name');
    } //Function ends

    /**
     * Test the response of the deleteGroup method with a valid group name.
     */
    #[Test]
    #[Depends('test_method_deleteGroup_exits')]
    public function test_deleteGroup_method_with_valid_group_name(): void
    {
        $response = $this->client->deleteGroup(self::SAMPLE_DATA['group_name']);
        $this->assertIsArray($response->toArray());
    } //Function ends

} // Class ends
