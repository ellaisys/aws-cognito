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
use Ellaisys\Cognito\Traits\ManagePasskeyWebAuthnAction;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('trait'), Group('passkey'), Group('webauthn')]
#[CoversTrait(ManagePasskeyWebAuthnAction::class)]
class ManagePasskeyWebAuthnActionTest extends TestCase
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
     * Test if the startWebAuthnRegistration method exists.
     */
    #[Test]
    public function test_method_startWebAuthnRegistration_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'startWebAuthnRegistration'));
    } // Function ends

    /**
     * Test the response of the startWebAuthnRegistration method with invalid data.
     */
    #[Test]
    #[Depends('test_method_startWebAuthnRegistration_exists')]
    public function test_startWebAuthnRegistration_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->startWebAuthnRegistration(
            TestCase::SAMPLE_INVALID_DATA['access_token']
        );
    } // Function ends

    /**
     * Test if the completeWebAuthnRegistration method exists.
     */
    #[Test]
    public function test_method_completeWebAuthnRegistration_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'completeWebAuthnRegistration'));
    } // Function ends

    /**
     * Test the response of the completeWebAuthnRegistration method with invalid data.
     */
    #[Test]
    #[Depends('test_method_completeWebAuthnRegistration_exists')]
    public function test_completeWebAuthnRegistration_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->completeWebAuthnRegistration(
            TestCase::SAMPLE_INVALID_DATA['access_token'],
            ['some_data' => 'invalid_value']
        );
    } // Function ends

    /**
     * Test if the listWebAuthnCredentials method exists.
     */
    #[Test]
    public function test_method_listWebAuthnCredentials_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'listWebAuthnCredentials'));
    } // Function ends

    /**
     * Test the response of the listWebAuthnCredentials method with invalid data.
     */
    #[Test]
    #[Depends('test_method_listWebAuthnCredentials_exists')]
    public function test_listWebAuthnCredentials_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->listWebAuthnCredentials(
            TestCase::SAMPLE_INVALID_DATA['access_token'],
            20,
            TestCase::SAMPLE_INVALID_DATA['pagination_token']
        );
    } // Function ends

    /**
     * Test if the deleteWebAuthnCredential method exists.
     */
    #[Test]
    public function test_method_deleteWebAuthnCredential_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'deleteWebAuthnCredential'));
    } // Function ends

    /**
     * Test the response of the deleteWebAuthnCredential method with invalid data.
     */
    #[Test]
    #[Depends('test_method_deleteWebAuthnCredential_exists')]
    public function test_deleteWebAuthnCredential_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->deleteWebAuthnCredential(
            TestCase::SAMPLE_INVALID_DATA['access_token'],
            'invalid_credential_id'
        );
    } // Function ends

    /**
     * Test if the authWebAuthnCredential method exists.
     */
    #[Test]
    public function test_method_authWebAuthnCredential_exists(): void
    {
        $this->assertTrue(method_exists($this->client, 'authWebAuthnCredential'));
    } // Function ends

    /**
     * Test the response of the authWebAuthnCredential method with invalid data.
     */
    #[Test]
    #[Depends('test_method_authWebAuthnCredential_exists')]
    public function test_authWebAuthnCredential_method_with_invalid_data(): void
    {
        $this->expectException(HttpException::class);
        $this->client->authWebAuthnCredential(
            CognitoAuthFlowTypes::USER_AUTH,
            TestCase::SAMPLE_INVALID_DATA['username'],
            'some_challenge'
        );
    } // Function ends
} // Class ends
