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

use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;
use Ellaisys\Cognito\Tests\Traits\AuthenticationTrait;

#[Group('web'), Group('invite'), Group('feature')]
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
         * Override the configuration at runtime
         */
        Config::set('cognito.registration_enabled', false);

        // Create a unique name and email for the test
        $this->user = [
            'name' => 'Testbench Invite Temp User',
            'email' => 'ellaisys+tb_tmp_invite_' . random_int(1000, 9999) . '@gmail.com'
        ];

        // Authenticate the user before running the tests
        $this->authenticateWeb();
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
