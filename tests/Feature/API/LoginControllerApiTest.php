<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Feature\API;

use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;

use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;
use Ellaisys\Cognito\Tests\Traits\AuthenticationTrait;

#[Group('api'), Group('login'), Group('feature')]
class LoginControllerApiTest extends ApiTestCase
{
    use AwsCognitoTrait;
    use AuthenticationTrait;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * Override the configuration at runtime
         */
        Config::set('cognito.mfa_setup', 'OFF');
        Config::set('cognito.mfa_type', ['SOFTWARE_TOKEN_MFA']);
        Config::set('cognito.allowed_auth_flows', [
            'ALLOW_USER_PASSWORD_AUTH',
        ]);
    } //Function ends

    /**
     * Test that the user pool client configuration allows for password
     * authentication.
     */
    #[Test]
    public function test_valid_settings_for_password_auth(): void
    {
        $this->assertTrue($this->validateUserPoolClientConfig(
            Enums\CognitoAuthFlowTypes::USER_PASSWORD_AUTH));
    } //Function ends

    /**
     * Test that the login routes validate the required payload.
     */
    #[Test]
    #[Depends('test_valid_settings_for_password_auth')]
    public function test_login_routes_validate_required_payload(): void
    {
        $this->assertValidationError('POST', '/login');
    } //Function ends

    /**
     * Test user can authenticate with valid credentials.
     */
    #[Test]
    #[Depends('test_valid_settings_for_password_auth')]
    public function test_user_can_authenticate_with_valid_credentials(): void
    {
        $this->authenticateApi();
    } //Function ends

    /**
     * Test that a user cannot authenticate with invalid credentials.
     */
    #[Test]
    #[Depends('test_user_can_authenticate_with_valid_credentials')]
    public function test_user_cannot_authenticate_with_invalid_credentials(): void
    {
        // Get invalid credentials for the user
        $invalidCredentials = $this->getInvalidCredentials();
        $payload = [
            'username' => $invalidCredentials['email'],
            'password' => $invalidCredentials['password'],
        ];

        $response = $this->postJson($this->apiPath('/login'), $payload);
        $this->assertFailure($response, 401);
    } //Function ends

    /**
     * Test that the challenge routes validate the required payload.
     */
    #[Test]
    public function test_challenge_routes_validate_required_payload(): void
    {
        $this->assertValidationError('POST', '/login/challenge');
    } //Function ends

} //Class ends
