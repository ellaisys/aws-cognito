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
use PHPUnit\Framework\Attributes\Depends;

use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;
use Ellaisys\Cognito\Tests\Traits\AuthenticationTrait;

class LoginTest extends TestCase
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
    } //Function ends

    /**
     * Test that the login form is accessible and returns a 200 status code.
     */
    #[Test]
    public function test_user_can_view_a_login_form(): void
    {
        $this->get(route('cognito.form.login'))
            ->assertStatus(200)
            ->assertSeeText('Login');
    } //Function ends

    /**
     * Test that the user pool client configuration allows for password authentication.
     */
    #[Test]
    public function test_valid_settings_for_password_auth(): void
    {
        $this->assertTrue($this->validateUserPoolClientConfig(
            Enums\CognitoAuthFlowTypes::USER_PASSWORD_AUTH));
    } //Function ends

    /**
     * Test that a user can log in with correct credentials.
     */
    #[Test]
    #[Depends('test_valid_settings_for_password_auth')]
    public function test_user_can_login_with_correct_credentials(): void
    {
        // Get valid credentials for the user
        $credentials = $this->getValidCredentials();
        $payload = [
            'username' => $credentials['email'] ?? '',
            'password' => $credentials['password'] ?? '',
        ];

        $this->post(route('cognito.action.login.submit'), $payload)
            ->assertStatus(302)
            ->assertRedirect(route('cognito.home'))
            ->assertSessionHas('status', 'success')
            ->assertSessionHas('message')
            ->assertSessionHas('claim')
            ->assertSessionHasNoErrors();

        // Assert that the user is authenticated
        $this->assertAuthenticated();

        if (session()->has('claim')) {
            self::$sessionAuthenticated = session()->all();
            self::$claim = session('claim');
        }

        // Assert that the claim is not null
        $this->assertClaimIsValid();
    } //Function ends

    /**
     * Test that the claim has an access token after a successful login.
     */
    #[Test]
    #[Depends('test_user_can_login_with_correct_credentials')]
    public function test_claim_has_access_token(): void
    {
        $this->assertClaimHasAccessToken();
    } //Function ends

    /**
     * Test that the claim has a refresh token after a successful login.
     */
    #[Test]
    #[Depends('test_user_can_login_with_correct_credentials')]
    public function test_claim_has_refresh_token(): void
    {
        $this->assertClaimHasRefreshToken();
    } //Function ends

    /**
     * Test that the claim has an ID token after a successful login.
     */
    #[Test]
    #[Depends('test_user_can_login_with_correct_credentials')]
    public function test_claim_has_id_token(): void
    {
        $this->assertClaimHasIdToken();
    } //Function ends

    /**
     * Test that a user cannot log in with incorrect credentials.
     */
    #[Test]
    #[Depends('test_valid_settings_for_password_auth')]
    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        // Get valid credentials for the user
        $credentials = $this->getValidCredentials();
        $payload = [
            'username' => $credentials['email'] ?? '',
            'password' => 'Invalidpassword@123',
        ];

        $this->post(route('cognito.action.login.submit'), $payload)
            ->assertStatus(302)
            ->assertSessionHas('status', 'error');
    } //Function ends

} //Class ends
