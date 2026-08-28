<?php

namespace Ellaisys\Cognito\Tests\Feature;

use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DependsExternal;

use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;
use Ellaisys\Cognito\Tests\Traits\AuthenticationTrait;

class InviteUserTest extends TestCase
{
    use AwsCognitoTrait;
    use AuthenticationTrait;

    private array $user;

    // Runs BEFORE every individual test method
    protected function setUp(): void
    {
        parent::setUp(); // Always good practice to call parent setup

        /**
         * Override the configuration at runtime to disable MFA and set the
         * MFA type to SOFTWARE_TOKEN_MFA
         */
        Config::set('cognito.mfa_setup', 'OFF');
        Config::set('cognito.mfa_type', ['SOFTWARE_TOKEN_MFA']);

        // Create a unique name and email for the test
        $name = 'Testbench Invite User ' . date('dmy');
        $email = 'ellaisys+tb_invite_' . date('dmyVHm') . '@gmail.com';

        $this->user = [
            'name' => $name,
            'email' => $email
        ];

        // Authenticate the user before running the tests
        $this->authenticate();
    } //Function ends

    /**
     * Test that the invitation page is accessible.
     */
    #[Test]
    public function test_web_invitation_page(): void
    {
        $this->withSession(self::$sessionAuthenticated)
            ->get(route('cognito.form.user.invite'))
            ->assertStatus(200)
            ->assertSeeText('Invite User');
    } //Function ends

    /**
     * Test that the invitation action works correctly without providing a
     * phone number and password.
     */
    #[Test]
    #[Depends('test_web_invitation_page')]
    public function test_web_invitation_action_without_phone_and_password(): void
    {
        $this->withSession(self::$sessionAuthenticated)
            ->post(route('cognito.action.invite.submit'), $this->user)
            ->assertStatus(302)
            ->assertRedirect(route('cognito.home'));
    } //Function ends

} //Class ends
