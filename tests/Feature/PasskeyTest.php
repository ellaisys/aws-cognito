<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Feature;

use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;

use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;
use Ellaisys\Cognito\Tests\Traits\AuthenticationTrait;

#[Group('passkey')]
class PasskeyTest extends TestCase
{
    use AwsCognitoTrait;
    use AuthenticationTrait;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * Override the configuration at runtime to disable MFA and set the
         * MFA type to SOFTWARE_TOKEN_MFA
         */
        Config::set('cognito.mfa_setup', 'OFF');
        Config::set('cognito.mfa_type', ['SOFTWARE_TOKEN_MFA']);
        Config::set('cognito.allowed_auth_flows', ['ALLOW_REFRESH_TOKEN_AUTH', 'ALLOW_USER_PASSWORD_AUTH', 'ALLOW_USER_AUTH']);
    } //Function ends

    /**
     * Test that the user pool client configuration allows for choice based
     * signin authentication.
     */
    #[Test]
    public function test_valid_settings_for_choice_based_signin(): void
    {
        $this->assertTrue($this->validateUserPoolClientConfig(
            Enums\CognitoAuthFlowTypes::USER_AUTH));

        // Set configuration to allow password authentication
        Config::set('cognito.allow_passkeys', true);
    } //Function ends

    /**
     * Test that a user can get choice based signin options
     */
    #[Test]
    #[Depends('test_valid_settings_for_choice_based_signin')]
    public function test_user_can_get_choice_based_signin_options(): void
    {
        $credentials = $this->getValidCredentials();
        $payload = [
            'username' => $credentials['email'] ?? ''
        ];

        $this->post(route('cognito.action.auth.passkey.challenge'), $payload)
            ->assertStatus(302)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('data');

        // Get the data from the session
        $data = session('data');

        // Assert that the session has the expected keys
        $this->assertArrayHasKey('session_token', $data);
        $this->assertArrayHasKey('status', $data);
        $this->assertEquals('challenge', $data['status']);
        $this->assertArrayHasKey('challenge_name', $data);
        $this->assertArrayHasKey('available_challenges', $data);
        $this->assertIsArray($data['available_challenges']);
    } //Function ends

    /**
     * Test that the user can initiate a WebAuthn registration
     */
    #[Test]
    #[Depends('test_valid_settings_for_choice_based_signin')]
    public function test_user_webauthn_registration(): void
    {
        // Authenticate the user before running the tests
        $this->authenticate();

        $this->withSession(self::$sessionAuthenticated)
            ->post(route('cognito.action.user.passkey.start'))
            ->assertStatus(302);

        // Get the data from the session
        $data = session()->has('data') ? session('data') : null;
        $this->assertNotNull($data, 'Session data is null.');

        // Assert that the session has the expected keys
        $this->assertArrayHasKey('CredentialCreationOptions', $data);
        $this->assertArrayHasKey('rp', $data['CredentialCreationOptions']);
        $this->assertArrayHasKey('user', $data['CredentialCreationOptions']);
        $this->assertArrayHasKey('challenge', $data['CredentialCreationOptions']);
        $this->assertArrayHasKey('pubKeyCredParams', $data['CredentialCreationOptions']);
    } //Function ends

} //Class end
