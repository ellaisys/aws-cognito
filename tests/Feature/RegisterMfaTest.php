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
use PHPUnit\Framework\Attributes\DependsExternal;

use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;
use Ellaisys\Cognito\Tests\Traits\AuthenticationTrait;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('feature'), Group('mfa'), Group('register-mfa')]
class RegisterMfaTest extends TestCase
{
    use AwsCognitoTrait;
    use AuthenticationTrait;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        // Set config values
        Config::set('cognito.mfa_setup', 'OPTIONAL');

        // Authenticate the user before running the tests
        $this->authenticateWeb();
    } // Function ends

    /**
     * Test activate action with authenticated user
     */
    #[Test]
    public function test_activate_action_with_authenticated_user(): void
    {
        $this->withSession(self::$sessionAuthenticated)
            ->get(route('cognito.form.user.mfa.activate'))
            ->assertStatus(200);
    } // Function ends

    /**
     * Test verify action with authenticated user
     */
    #[Test]
    public function test_verify_action_with_authenticated_user_invalid_code(): void
    {
        $payload = [
            'code' => '000000', // Invalid code for testing
        ];

        $this->withSession(self::$sessionAuthenticated)
            ->post(route('cognito.action.user.mfa.activate'), $payload)
            ->assertStatus(302)
            ->assertSessionHasErrors();
    } // Function ends

    /**
     * Test deactivate action with authenticated user
     */
    #[Test]
    public function test_deactivate_action_with_authenticated_user(): void
    {
        $this->withSession(self::$sessionAuthenticated)
            ->get(route('cognito.action.user.mfa.deactivate'))
            ->assertStatus(302)
            ->assertSessionHasNoErrors();
    } // Function ends

    /**
     * Test enable action with authenticated user
     */
    #[Test]
    public function test_enable_action_with_authenticated_user(): void
    {
        // Get valid credentials for the user
        $credentials = $this->getValidCredentials();
        $payload = [
            'username' => $credentials['email'],
        ];
        
        $this->withSession(self::$sessionAuthenticated)
            ->post(route('cognito.action.mfa.enable'), $payload)
            ->assertStatus(302)
            ->assertSessionHasErrors();
    } // Function ends

    /**
     * Test disable action with authenticated user
     */
    #[Test]
    public function test_disable_action_with_authenticated_user(): void
    {
        // Get valid credentials for the user
        $credentials = $this->getValidCredentials();
        $payload = [
            'username' => $credentials['email'],
        ];

        $this->withSession(self::$sessionAuthenticated)
            ->post(route('cognito.action.mfa.disable'), $payload)
            ->assertStatus(302);
    } // Function ends
} // Class ends
