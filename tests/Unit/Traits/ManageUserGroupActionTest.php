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
use Ellaisys\Cognito\Enums\CognitoChallengeTypes;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\EncryptionTypes;
use Ellaisys\Cognito\AwsCognitoClient;
use Ellaisys\Cognito\Traits\ManageUserGroupAction;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('trait'), Group('user-group')]
#[CoversTrait(ManageUserGroupAction::class)]
class ManageUserGroupActionTest extends TestCase
{
    private $client;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        //Create AWS Cognito Client
        $this->client = app()->make(AwsCognitoClient::class);
    } // Function ends

    /**
     * Test if the adminAddUserToGroup method exists.
     */
    #[Test]
    public function test_method_adminAddUserToGroup_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminAddUserToGroup'));
    } // Function ends

    /**
     * Test the response of the adminAddUserToGroup method with invalid data.
     */
    #[Test]
    #[Depends('test_method_adminAddUserToGroup_exists')]
    public function test_adminAddUserToGroup_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminAddUserToGroup(
            TestCase::SAMPLE_INVALID_DATA['username'],
            TestCase::SAMPLE_INVALID_DATA['user_group_name']
        );
    } // Function ends

    /**
     * Test the response of the adminAddUserToGroup method with valid data.
     */
    #[Test]
    #[Depends('test_method_adminAddUserToGroup_exists')]
    public function test_adminAddUserToGroup_method_with_valid_data(): void
    {
        // Get valid credentials for the user
        $credentials = $this->getValidCredentials();

        $response = $this->client->adminAddUserToGroup(
            $credentials['email'],
            TestCase::SAMPLE_DATA['user_group_name']
        );

        $data = $response->toArray();
        $this->assertIsArray($data);
    } // Function ends

    /**
     * Test if the adminRemoveUserFromGroup method exists.
     */
    #[Test]
    public function test_method_adminRemoveUserFromGroup_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminRemoveUserFromGroup'));
    } // Function ends

    /**
     * Test the response of the adminRemoveUserFromGroup method with invalid data.
     */
    #[Test]
    #[Depends('test_method_adminRemoveUserFromGroup_exists')]
    public function test_adminRemoveUserFromGroup_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminRemoveUserFromGroup(
            TestCase::SAMPLE_INVALID_DATA['username'],
            TestCase::SAMPLE_INVALID_DATA['user_group_name']
        );
    } // Function ends

    /**
     * Test the response of the adminRemoveUserFromGroup method with valid data.
     */
    #[Test]
    #[Depends('test_method_adminRemoveUserFromGroup_exists')]
    public function test_adminRemoveUserFromGroup_method_with_valid_data(): void
    {
        // Get valid credentials for the user
        $credentials = $this->getValidCredentials();

        $response = $this->client->adminRemoveUserFromGroup(
            $credentials['email'],
            TestCase::SAMPLE_DATA['user_group_name']
        );

        $data = $response->toArray();
        $this->assertIsArray($data);
    } // Function ends

    /**
     * Test if the adminListGroupsForUser method exists.
     */
    #[Test]
    public function test_method_adminListGroupsForUser_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminListGroupsForUser'));
    } // Function ends

    /**
     * Test the response of the adminListGroupsForUser method with invalid data.
     */
    #[Test]
    #[Depends('test_method_adminListGroupsForUser_exists')]
    public function test_adminListGroupsForUser_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminListGroupsForUser(
            TestCase::SAMPLE_INVALID_DATA['username'],
            TestCase::SAMPLE_INVALID_DATA['pagination_token']
        );
    } // Function ends

    /**
     * Test if the listUsersInGroup method exists.
     */
    #[Test]
    public function test_method_listUsersInGroup_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'listUsersInGroup'));
    } // Function ends

    /**
     * Test the response of the listUsersInGroup method with invalid data.
     */
    #[Test]
    #[Depends('test_method_listUsersInGroup_exists')]
    public function test_listUsersInGroup_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->listUsersInGroup(
            TestCase::SAMPLE_INVALID_DATA['user_group_name'],
            TestCase::SAMPLE_INVALID_DATA['pagination_token']
        );
    } // Function ends

    /**
     * Test the response of the listUsersInGroup method with valid data.
     */
    #[Test]
    #[Depends('test_method_listUsersInGroup_exists')]
    public function test_listUsersInGroup_method_with_valid_data(): void
    {
        $response = $this->client->listUsersInGroup(
            TestCase::SAMPLE_DATA['user_group_name']
        );
        $data = $response->toArray();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('Users', $data);
    } // Function ends
} // Class ends
