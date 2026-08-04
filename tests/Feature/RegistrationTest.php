<?php

namespace Ellaisys\Cognito\Tests\Feature;

use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;

use Ellaisys\Cognito\Tests\TestCase;

class RegistrationTest extends TestCase
{
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
        Config::set('cognito.mfa_type', 'SOFTWARE_TOKEN_MFA');

        // Create a unique name and email for the test
        $name = 'Testbench Register User ' . date('dmy');
        $email = 'ellaisys+tb_register_' . date('dmyVHm') . '@gmail.com';

        $this->user = [
            'name' => $name,
            'email' => $email
        ];
    }

    #[Test]
    public function test_web_registration_page(): void
    {
        $this->get(route('cognito.form.register'))
            ->assertStatus(200)
            ->assertSeeText('Register');
    }

    #[Test]
    public function test_web_registration_action_without_phone_and_password(): void
    {
        $this->post(route('cognito.action.register.submit'), $this->user)
            ->assertStatus(302)
            ->assertRedirect(route('cognito.form.register.verify'));
    }
}
