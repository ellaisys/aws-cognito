<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Unit;

use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\EncryptionTypes;
use Ellaisys\Cognito\AwsCognitoClient;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('trait'), Group('base')]
#[CoversClass(AwsCognitoClient::class)]
class AwsCognitoClientTest extends TestCase
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
     * Test if the getCognitoIdentityProviderClient method exists.
     */
    #[Test]
    public function test_method_getCognitoIdentityProviderClient_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'getCognitoIdentityProviderClient'));
    } // Function ends

    /**
     * Test the response of the getCognitoIdentityProviderClient method.
     */
    #[Test]
    #[Depends('test_method_getCognitoIdentityProviderClient_exists')]
    public function test_getCognitoIdentityProviderClient_method(): void
    {
        $response = $this->client->getCognitoIdentityProviderClient();
        $this->assertNotNull($response);
        $this->assertIsObject($response);
    } // Function ends

    /**
     * Test if the authenticate method exists.
     */
    #[Test]
    public function test_method_authenticate_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'authenticate'));
    } // Function ends

    /**
     * Test if the register method exists.
     */
    #[Test]
    public function test_method_register_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'register'));
    } // Function ends

    /**
     * Test if the sendResetLink method exists.
     */
    #[Test]
    public function test_method_sendResetLink_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'sendResetLink'));
    } // Function ends

    /**
     * Test if the resetPassword method exists.
     */
    #[Test]
    public function test_method_resetPassword_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'resetPassword'));
    } // Function ends

    /**
     * Test if the inviteUser method exists.
     */
    #[Test]
    public function test_method_inviteUser_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'inviteUser'));
    } // Function ends

    /**
     * Test the response of the inviteUser method with invalid
     * data.
     */
    #[Test]
    #[Depends('test_method_inviteUser_exists')]
    public function test_inviteUser_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->inviteUser(
            TestCase::SAMPLE_INVALID_DATA['email'],
            TestCase::SAMPLE_INVALID_DATA['password'],
            TestCase::SAMPLE_INVALID_DATA['attributes'],
            TestCase::SAMPLE_INVALID_DATA['client_metadata'],
            TestCase::SAMPLE_INVALID_DATA['message_action'],
            TestCase::SAMPLE_INVALID_DATA['user_group_name']
        );
    } // Function ends

    /**
     * Test the response of the inviteUser method with existing
     * user data.
     */
    #[Test]
    #[Depends('test_method_inviteUser_exists')]
    public function test_inviteUser_method_with_existing_user_data(): void
    {
        $credentials = $this->getValidCredentials();

        $this->expectException(HttpException::class);
        $this->client->inviteUser(
            $credentials['email'],
            TestCase::SAMPLE_INVALID_DATA['password'],
            [],
            TestCase::SAMPLE_INVALID_DATA['client_metadata'],
            TestCase::SAMPLE_INVALID_DATA['message_action'],
            TestCase::SAMPLE_INVALID_DATA['user_group_name']
        );
    } // Function ends

    /**
     * Test if the confirmPassword method exists.
     */
    #[Test]
    public function test_method_confirmPassword_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'confirmPassword'));
    } // Function ends

    /**
     * Test if the changePassword method exists.
     */
    #[Test]
    public function test_method_changePassword_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'changePassword'));
    } // Function ends

    /**
     * Test the response of the changePassword method with invalid
     * data.
     */
    #[Test]
    #[Depends('test_method_changePassword_exists')]
    public function test_changePassword_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->changePassword(
            TestCase::SAMPLE_INVALID_DATA['access_token'],
            TestCase::SAMPLE_INVALID_DATA['password'],
            TestCase::SAMPLE_INVALID_DATA['password'] . 'new'
        );
    } // Function ends





    /**
     * Test if the confirmSignUp method exists.
     */
    #[Test]
    public function test_method_confirmSignUp_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'confirmSignUp'));
    } // Function ends

    /**
     * Test the response of the confirmSignUp method with invalid
     * data.
     */
    #[Test]
    #[Depends('test_method_confirmSignUp_exists')]
    public function test_confirmSignUp_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->confirmSignUp(
            TestCase::SAMPLE_INVALID_DATA['username'],
            TestCase::SAMPLE_INVALID_DATA['confirmation_code'],
            TestCase::SAMPLE_INVALID_DATA['client_metadata'],
        );
    } // Function ends




    /**
     * Test if the resendConfirmationCode method exists.
     */
    #[Test]
    public function test_method_resendConfirmationCode_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'resendConfirmationCode'));
    } // Function ends

    /**
     * Test the response of the resendConfirmationCode method with invalid
     * data.
     */
    #[Test]
    #[Depends('test_method_resendConfirmationCode_exists')]
    public function test_resendConfirmationCode_method_with_invalid_data(): void
    {
        $response = $this->client->resendConfirmationCode(
            TestCase::SAMPLE_INVALID_DATA['username'],
            TestCase::SAMPLE_INVALID_DATA['client_metadata'],
        );
        $data = $response->toArray();

        $this->assertIsArray($data);
    } // Function ends





    /**
     * Test if the updateUserAttributes method exists.
     */
    #[Test]
    public function test_method_updateUserAttributes_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'updateUserAttributes'));
    } // Function ends

    /**
     * Test the response of the updateUserAttributes method with invalid
     * data.
     */
    #[Test]
    #[Depends('test_method_updateUserAttributes_exists')]
    public function test_updateUserAttributes_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->updateUserAttributes(
            TestCase::SAMPLE_INVALID_DATA['username'],
            [],
            TestCase::SAMPLE_INVALID_DATA['client_metadata'],
        );
    } // Function ends







    /**
     * Test if the refreshToken method exists.
     */
    #[Test]
    public function test_method_refreshToken_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'refreshToken'));
    } // Function ends

    /**
     * Test the response of the refreshToken method with invalid
     * data.
     */
    #[Test]
    #[Depends('test_method_refreshToken_exists')]
    public function test_refreshToken_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->refreshToken(
            TestCase::SAMPLE_INVALID_DATA['refresh_token'],
            TestCase::SAMPLE_INVALID_DATA['username'],
            TestCase::SAMPLE_INVALID_DATA['device_key'],
            TestCase::SAMPLE_INVALID_DATA['client_metadata'],
        );
    } // Function ends

    /**
     * Test if the revokeToken method exists.
     */
    #[Test]
    public function test_method_revokeToken_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'revokeToken'));
    } // Function ends

    /**
     * Test the response of the revokeToken method with invalid
     * data.
     */
    #[Test]
    #[Depends('test_method_revokeToken_exists')]
    public function test_revokeToken_method_with_invalid_data(): void
    {
        $response = $this->client->revokeToken(
            TestCase::SAMPLE_INVALID_DATA['refresh_token']
        );
        $data = $response->toArray();

        $this->assertIsArray($data);
    } // Function ends

    /**
     * Test if the signOut method exists.
     */
    #[Test]
    public function test_method_signOut_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'signOut'));
    } // Function ends

    /**
     * Test the response of the signOut method with invalid
     * data.
     */
    #[Test]
    #[Depends('test_method_signOut_exists')]
    public function test_signOut_method_with_invalid_data(): void
    {
        $response = $this->client->signOut(
            TestCase::SAMPLE_INVALID_DATA['access_token']
        );
        $this->assertTrue($response);
    } // Function ends

} // Class ends
