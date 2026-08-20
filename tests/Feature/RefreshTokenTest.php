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
use PHPUnit\Framework\Attributes\DependsExternal;

use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;

class RefreshTokenTest extends TestCase
{
    use AwsCognitoTrait;

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
     * Test that the user pool client configuration allows for refresh token authentication.
     */
    #[Test]
    public function test_valid_settings_for_refresh_auth(): void
    {
        $this->assertTrue($this->validateUserPoolClientConfig(
            Enums\CognitoAuthFlowTypes::REFRESH_TOKEN_AUTH));
    } //Function ends

    /**
     * Test that a user can log in with a correct refresh token
     */
    #[Test]
    #[Depends('test_valid_settings_for_refresh_auth')]
    #[DependsExternal(LoginTest::class, 'test_user_can_login_with_correct_credentials')]
    public function test_user_can_login_with_correct_refresh_token(): void
    {
        $claim = self::$claim ?? null;
        $this->assertNotNull($claim, 'Claim is null.');

        $payload = [
            'username' => $claim['username'] ?? '',
            'refresh_token' => $claim['data']['RefreshToken'] ?? '',
        ];

        $this->withSession(self::$sessionAuthenticated)
            ->post(route('cognito.action.session.revalidate'), $payload)
            ->assertStatus(302)
            ->assertRedirect(route('cognito.home'))
            ->assertSessionHas('status', 'success')
            ->assertSessionHas('message')
            ->assertSessionHas('claim')
            ->assertSessionHasNoErrors();

        // Assert that the user is authenticated
        $this->assertAuthenticated();

        if (session()->has('claim')) {
            self::$claim = session('claim');
        }

        // Assert that the claim is not null
        $this->assertNotNull(self::$claim, 'Claim is null.');
        $this->assertArrayHasKey('data', self::$claim, 'Claim does not contain the expected "data" key.');
    } //Function ends

    /**
     * Test that the claim has an access token after a successful login.
     */
    #[Test]
    #[Depends('test_user_can_login_with_correct_refresh_token')]
    public function test_claim_has_access_token(): void
    {
        $this->assertArrayHasKey('AccessToken', self::$claim['data'], 'Access token is missing in the claim.');
        $this->assertNotEmpty(self::$claim['data']['AccessToken'], 'Access token is empty in the claim.');
    } //Function ends

    /**
     * Test that the claim has a refresh token after a successful login.
     */
    #[Test]
    #[Depends('test_user_can_login_with_correct_refresh_token')]
    public function test_claim_has_refresh_token(): void
    {
        $this->assertArrayHasKey('RefreshToken', self::$claim['data'], 'Refresh token is missing in the claim.');
        $this->assertNotEmpty(self::$claim['data']['RefreshToken'], 'Refresh token is empty in the claim.');
    } //Function ends

    /**
     * Test that the claim has an ID token after a successful login.
     */
    #[Test]
    #[Depends('test_user_can_login_with_correct_refresh_token')]
    public function test_claim_has_id_token(): void
    {
        $this->assertArrayHasKey('IdToken', self::$claim['data'], 'ID token is missing in the claim.');
        $this->assertNotEmpty(self::$claim['data']['IdToken'], 'ID token is empty in the claim.');
    } //Function ends

    /**
     * Test that a user cannot log in with incorrect refresh token.
     */
    #[Test]
    #[Depends('test_valid_settings_for_refresh_auth')]
    public function test_user_cannot_authenticate_with_incorrect_refresh_token(): void
    {
        // Get valid credentials for the user
        $credentials = $this->getValidCredentials();
        $payload = [
            'username' => $credentials['username'] ?? '',
            'refresh_token' => 'InvalidRefreshToken@123',
        ];

        $this->post(route('cognito.action.session.revalidate'), $payload)
            ->assertStatus(302)
            ->assertSessionHas('status', 'error')
            ->assertSessionHasErrors(['error' => 'Invalid Refresh Token']);
    } //Function ends

} //Class ends
