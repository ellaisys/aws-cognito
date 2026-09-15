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

#[Group('api'), Group('login'), Group('srp'), Group('feature')]
class LoginSrpControllerApiTest extends ApiTestCase
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
    } //Function ends

    /**
     * Test that the user pool client configuration allows for SRP authentication.
     */
    #[Test]
    public function test_valid_settings_for_srp_auth(): void
    {
        $this->assertTrue($this->validateUserPoolClientConfig(
            Enums\CognitoAuthFlowTypes::USER_SRP_AUTH));
    } //Function ends

    /**
     * Test that the login routes validate the required payload.
     */
    #[Test]
    #[Depends('test_valid_settings_for_srp_auth')]
    public function test_login_routes_validate_required_payload(): void
    {
        $this->assertValidationError('POST', '/login/srp');
    } //Function ends

    /**
     * Test user can authenticate with valid credentials.
     */
    #[Test]
    #[Depends('test_valid_settings_for_srp_auth')]
    public function test_user_can_authenticate_with_valid_srp_credentials(): void
    {
        // Get valid credentials for the user
        $credentials = $this->getValidCredentials();
        $payload = [
            'username' => $credentials['email']
        ];

        $response = $this->postJson($this->apiPath('/login/srp'), $payload);
        $this->assertSuccess($response);
    } //Function ends

    /**
     * Test that a user cannot authenticate with invalid credentials.
     */
    #[Test]
    #[Depends('test_valid_settings_for_srp_auth')]
    public function test_user_cannot_authenticate_with_invalid_srp_credentials(): void
    {
        // Get invalid credentials for the user
        $invalidCredentials = $this->getInvalidCredentials();
        $payload = [
            'username' => $invalidCredentials['email']
        ];

        $response = $this->postJson($this->apiPath('/login/srp'), $payload);
        $this->assertFailure($response, 401);
    } //Function ends

} //Class ends
