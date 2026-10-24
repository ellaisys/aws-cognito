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
use Ellaisys\Cognito\Traits\ManageUserAuthAction;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('trait'), Group('user-auth')]
#[CoversTrait(ManageUserAuthAction::class)]
class ManageUserAuthActionTest extends TestCase
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
     * Test if the initiateAuth method exists.
     */
    #[Test]
    public function test_method_initiateAuth_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'initiateAuth'));
    } // Function ends

    /**
     * Test the response of the initiateAuth method with invalid data.
     */
    #[Test]
    #[Depends('test_method_initiateAuth_exists')]
    public function test_initiateAuth_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->initiateAuth(
            CognitoAuthFlowTypes::USER_PASSWORD_AUTH,
            [
                'USERNAME' => TestCase::SAMPLE_INVALID_DATA['username'],
                'PASSWORD' => TestCase::SAMPLE_INVALID_DATA['password'],
            ],
            TestCase::SAMPLE_INVALID_DATA['username']
        );
    } // Function ends

    /**
     * Test if the getUser method exists.
     */
    #[Test]
    public function test_method_getUser_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'getUser'));
    } // Function ends

    /**
     * Test the response of the getUser method with invalid data.
     */
    #[Test]
    #[Depends('test_method_getUser_exists')]
    public function test_getUser_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->getUser(
            TestCase::SAMPLE_INVALID_DATA['access_token']
        );
    } // Function ends

    /**
     * Test if the respondToAuthChallenge method exists.
     */
    #[Test]
    public function test_method_respondToAuthChallenge_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'respondToAuthChallenge'));
    } // Function ends

    /**
     * Test the response of the respondToAuthChallenge method with invalid data.
     */
    #[Test]
    #[Depends('test_method_respondToAuthChallenge_exists')]
    public function test_respondToAuthChallenge_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->respondToAuthChallenge(
            CognitoChallengeTypes::SOFTWARE_TOKEN_MFA,
            TestCase::SAMPLE_INVALID_DATA['session_value'],
            'invalid_challenge_value',
            TestCase::SAMPLE_INVALID_DATA['username']
        );
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
     * Test the response of the updateUserAttributes method with invalid data.
     */
    #[Test]
    #[Depends('test_method_updateUserAttributes_exists')]
    public function test_updateUserAttributes_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->updateUserAttributes(
            TestCase::SAMPLE_INVALID_DATA['username'],
            TestCase::SAMPLE_INVALID_DATA['attributes'],
            TestCase::SAMPLE_INVALID_DATA['client_metadata'],
        );
    } // Function ends
} // Class ends
