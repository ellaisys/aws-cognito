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
use Ellaisys\Cognito\Tests\Traits\AuthenticationTrait;

class ChangePasswordTest extends TestCase
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
        $this->authenticate();
    } //Function ends

    /**
     * Test loading the change password page.
     */
    #[Test]
    public function test_load_change_password_web_page(): void
    {
        $this->withSession(self::$sessionAuthenticated)
            ->get(route('cognito.form.change.password'))
            ->assertStatus(200)
            ->assertSeeText('Change Password')
            ->assertSee('Existing Password')
            ->assertSee('New Password')
            ->assertSee('Confirm Password');
    } // Function ends

    /**
     * Test changing the password with valid data.
     */
    #[Test]
    #[Depends('test_load_change_password_web_page')]
    public function test_change_password_action_with_valid_data(): void
    {
        // Get valid credentials for the user
        $credentials = $this->getValidCredentials();
        $payload = [
            'email' => $credentials['email'] ?? null,
            'password' => $credentials['password'] ?? '',
            'new_password' => $credentials['password'],
            'new_password_confirmation' => $credentials['password'],
        ];

        $this->withSession(self::$sessionAuthenticated)
            ->post(route('cognito.action.change.password'), $payload)
            ->assertStatus(302);
    } // Function ends

} //Class ends
