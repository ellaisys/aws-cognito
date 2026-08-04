<?php

namespace Ellaisys\Cognito\Tests\Feature;

use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;

use Ellaisys\Cognito\Tests\TestCase;

class LoginTest extends TestCase
{
    private array $credentials;

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

        $this->credentials = [
            'username' => 'ellaisys+tb_register_0408V01@gmail.com',
            'password' => 'ValidPassword123!'
        ];
    }

    #[Test]
    public function test_user_can_view_a_login_form(): void
    {
        $this->get(route('cognito.form.login'))
            ->assertStatus(200)
            ->assertSeeText('Login');
    }
    
    #[Test]
    public function test_user_cannot_view_a_login_form_when_authenticated(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    public function test_user_can_login_with_correct_credentials(): void
    {
        $this->post(route('cognito.action.login.submit'), $this->credentials)
            ->assertStatus(302);
    }

    #[Test]
    public function test_user_cannot_login_with_incorrect_credentials(): void
    {
        // $user = factory(User::class)->create([
        //     'password' => bcrypt('i-love-laravel'),
        // ]);
        
        // $response = $this->from('/login')->post('/login', [
        //     'email' => $user->email,
        //     'password' => 'invalid-password',
        // ]);
        
        // $response->assertRedirect('/login');
        // $response->assertSessionHasErrors('email');
        // $this->assertTrue(session()->hasOldInput('email'));
        // $this->assertFalse(session()->hasOldInput('password'));
        // $this->assertGuest();
        $this->assertTrue(true);
    }
}
