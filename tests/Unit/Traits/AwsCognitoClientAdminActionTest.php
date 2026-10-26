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
use Illuminate\Support\Facades\Password;

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
use Ellaisys\Cognito\Traits\AwsCognitoClientAdminAction;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('trait'), Group('admin')]
#[CoversTrait(AwsCognitoClientAdminAction::class)]
class AwsCognitoClientAdminActionTest extends TestCase
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
     * Test if the adminConfirmSignUp method exists.
     */
    #[Test]
    public function test_method_adminConfirmSignUp_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminConfirmSignUp'));
    } // Function ends

    /**
     * Test the response of the adminConfirmSignUp method with invalid
     * username.
     */
    #[Test]
    #[Depends('test_method_adminConfirmSignUp_exists')]
    public function test_adminConfirmSignUp_method_with_invalid_username(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminConfirmSignUp(
            TestCase::SAMPLE_INVALID_DATA['username'],
            ['some_param' => 'some_value']
        );
    } // Function ends

    /**
     * Test if the adminEnableUser method exists.
     */
    #[Test]
    public function test_method_adminEnableUser_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminEnableUser'));
    } // Function ends

    /**
     * Test the response of the adminEnableUser method with invalid
     * username.
     */
    #[Test]
    #[Depends('test_method_adminEnableUser_exists')]
    public function test_adminEnableUser_method_with_invalid_username(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminEnableUser(
            TestCase::SAMPLE_INVALID_DATA['username']
        );
    } // Function ends

    /**
     * Test if the adminDisableUser method exists.
     */
    #[Test]
    public function test_method_adminDisableUser_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminDisableUser'));
    } // Function ends

    /**
     * Test the response of the adminDisableUser method with invalid
     * username.
     */
    #[Test]
    #[Depends('test_method_adminDisableUser_exists')]
    public function test_adminDisableUser_method_with_invalid_username(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminDisableUser(
            TestCase::SAMPLE_INVALID_DATA['username']
        );
    } // Function ends

    /**
     * Test if the adminUserGlobalSignOut method exists.
     */
    #[Test]
    public function test_method_adminUserGlobalSignOut_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminUserGlobalSignOut'));
    } // Function ends

    /**
     * Test the response of the adminUserGlobalSignOut method with invalid
     * username.
     */
    #[Test]
    #[Depends('test_method_adminUserGlobalSignOut_exists')]
    public function test_adminUserGlobalSignOut_method_with_invalid_username(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminUserGlobalSignOut(
            TestCase::SAMPLE_INVALID_DATA['username']
        );
    } // Function ends

    /**
     * Test the response of the adminUserGlobalSignOut method with valid
     * username.
     */
    #[Test]
    #[Depends('test_method_adminUserGlobalSignOut_exists')]
    public function test_adminUserGlobalSignOut_method_with_valid_username(): void
    {
        $credentials = $this->getValidCredentials();

        $response = $this->client->adminUserGlobalSignOut(
            $credentials['email']
        );
        $this->assertNotNull($response);
        $this->assertIsBool($response);
        $this->assertTrue($response);
    } // Function ends

    /**
     * Test if the invalidatePassword method exists.
     */
    #[Test]
    public function test_method_invalidatePassword_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'invalidatePassword'));
    } // Function ends

    /**
     * Test the response of the invalidatePassword method with invalid
     * data.
     */
    #[Test]
    #[Depends('test_method_invalidatePassword_exists')]
    public function test_invalidatePassword_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->invalidatePassword(
            TestCase::SAMPLE_INVALID_DATA['username']
        );
    } // Function ends

    /**
     * Test if the adminGetUser method exists.
     */
    #[Test]
    public function test_method_adminGetUser_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminGetUser'));
    } // Function ends

    /**
     * Test the response of the adminGetUser method with invalid
     * username.
     */
    #[Test]
    #[Depends('test_method_adminGetUser_exists')]
    public function test_adminGetUser_method_with_invalid_username(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminGetUser(
            TestCase::SAMPLE_INVALID_DATA['username']
        );
    } // Function ends

    /**
     * Test the response of the adminGetUser method with valid
     * username.
     */
    #[Test]
    #[Depends('test_method_adminGetUser_exists')]
    public function test_adminGetUser_method_with_valid_username(): void
    {
        $credentials = $this->getValidCredentials();

        $response = $this->client->adminGetUser(
            $credentials['email']
        );
        
        $data = $response->toArray();

        $this->assertIsArray($data);
        $this->assertArrayHasKey('Username', $data);
        $this->assertEquals($credentials['username'], $data['Username']);
    } // Function ends

    /**
     * Test if the deleteUser method exists.
     */
    #[Test]
    public function test_method_deleteUser_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'deleteUser'));
    } // Function ends

    /**
     * Test the response of the deleteUser method with invalid
     * username.
     */
    #[Test]
    #[Depends('test_method_deleteUser_exists')]
    public function test_deleteUser_method_with_invalid_username(): void
    {
        // Set configuration for the test if needed
        Config::set('cognito.delete_user', true);

        $this->expectException(HttpException::class);
        $this->client->deleteUser(
            TestCase::SAMPLE_INVALID_DATA['username']
        );
    } // Function ends

    /**
     * Test the response of the deleteUser method with disabled
     * configuration.
     */
    #[Test]
    #[Depends('test_method_deleteUser_exists')]
    public function test_deleteUser_method_with_disabled_configuration(): void
    {
        // Set configuration for the test if needed
        Config::set('cognito.delete_user', false);

        $this->expectException(HttpException::class);
        $this->client->deleteUser(
            TestCase::SAMPLE_INVALID_DATA['username']
        );
    } // Function ends

    /**
     * Test if the adminSetUserPassword method exists.
     */
    #[Test]
    public function test_method_adminSetUserPassword_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminSetUserPassword'));
    } // Function ends

    /**
     * Test the response of the adminSetUserPassword method with invalid
     * data.
     */
    #[Test]
    #[Depends('test_method_adminSetUserPassword_exists')]
    public function test_adminSetUserPassword_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminSetUserPassword(
            TestCase::SAMPLE_INVALID_DATA['username'],
            'invalid_password'
        );
    } // Function ends

    /**
     * Test the response of the adminSetUserPassword method with valid
     * data.
     */
    #[Test]
    #[Depends('test_method_adminSetUserPassword_exists')]
    public function test_adminSetUserPassword_method_with_valid_data(): void
    {
        // Get valid credentials for the test
        $credentials = $this->getValidCredentials();

        $response = $this->client->adminSetUserPassword(
            $credentials['username'],
            $credentials['password'],
            true
        );
        $this->assertNotNull($response);
        $this->assertEquals(Password::PASSWORD_RESET, $response);
    } // Function ends

    /**
     * Test if the adminRespondToAuthChallenge method exists.
     */
    #[Test]
    public function test_method_adminRespondToAuthChallenge_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminRespondToAuthChallenge'));
    } // Function ends

    /**
     * Test the response of the adminRespondToAuthChallenge method with invalid
     * data.
     */
    #[Test]
    #[Depends('test_method_adminRespondToAuthChallenge_exists')]
    public function test_adminRespondToAuthChallenge_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminRespondToAuthChallenge(
            CognitoChallengeTypes::PASSWORD,
            TestCase::SAMPLE_INVALID_DATA['session_value'],
            'invalid_challenge_value',
            TestCase::SAMPLE_INVALID_DATA['username']
        );
    } // Function ends

    /**
     * Test if the adminInitiateAuth method exists.
     */
    #[Test]
    public function test_method_adminInitiateAuth_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminInitiateAuth'));
    } // Function ends

    /**
     * Test the response of the adminInitiateAuth method with invalid
     * data.
     */
    #[Test]
    #[Depends('test_method_adminInitiateAuth_exists')]
    public function test_adminInitiateAuth_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminInitiateAuth(
            CognitoAuthFlowTypes::ADMIN_NO_SRP_AUTH,
            [],
            TestCase::SAMPLE_INVALID_DATA['username']
        );
    } // Function ends

    /**
     * Test if the adminUpdateUserAttributes method exists.
     */
    #[Test]
    public function test_method_adminUpdateUserAttributes_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'adminUpdateUserAttributes'));
    } // Function ends

    /**
     * Test the response of the adminUpdateUserAttributes method with invalid
     * data.
     */
    #[Test]
    #[Depends('test_method_adminUpdateUserAttributes_exists')]
    public function test_adminUpdateUserAttributes_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->adminUpdateUserAttributes(
            TestCase::SAMPLE_INVALID_DATA['username'],
            [],
            TestCase::SAMPLE_INVALID_DATA['client_metadata']
        );
    } // Function ends

} // Class ends
