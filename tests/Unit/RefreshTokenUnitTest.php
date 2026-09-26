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

use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;
use Ellaisys\Cognito\Tests\Traits\AuthenticationTrait;

#[Group('web'), Group('refresh'), Group('unit')]
class RefreshTokenUnitTest extends TestCase
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

        // Authenticate the user before running the tests
        $this->authenticateWeb();
    } //Function ends

    /**
     * Test that a user cannot refresh the token with an invalid payload.
     */
    #[Test]
    public function test_refresh_token_with_invalid_payload_datatype(): void
    {
        $payload = [
            'username' => true,
            'refresh_token' => 'invalid_refresh_token',
        ];

        $this->withSession(self::$sessionAuthenticated)
            ->post(route('cognito.action.session.refresh'), $payload)
            ->assertStatus(302)
            ->assertSessionHasErrors();
    } //Function ends

    /**
     * Test that a user cannot refresh the token with an invalid payload
     * when the username attribute is missing from the configuration.
     */
    #[Test]
    public function test_refresh_token_with_invalid_payload_datatype_for_username_attribute_missing(): void
    {
        Config::set('cognito.sign_in_username_attributes', []);

        $credentials = $this->getInvalidCredentials();
        $payload = [
            'username' => $credentials['username'],
            'refresh_token' => 'invalid_refresh_token',
        ];

        $this->withSession(self::$sessionAuthenticated)
            ->post(route('cognito.action.session.refresh'), $payload)
            ->assertStatus(302)
            ->assertSessionHasErrors();
    } //Function ends

    /**
     * Test that a user cannot refresh the token with no payload
     * when the username attribute is missing from the configuration.
     * Based on the pool configuration, that will authenticate the user, but
     * in this scenario, the refresh token request will fail.
     * This test is for coverage purposes only.
     */
    #[Test]
    public function test_refresh_token_with_no_payload_for_username_attribute_missing(): void
    {
        Config::set('cognito.sign_in_username_attributes', []);

        $this->withSession(self::$sessionAuthenticated)
            ->post(route('cognito.action.session.refresh'))
            ->assertStatus(302)
            ->assertSessionHasErrors();
    } //Function ends

} //Class ends
