<?php

namespace Ellaisys\Cognito\Tests\Feature;

use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;

use Ellaisys\Cognito\Tests\TestCase;

#[Group('web'), Group('register')]
class RegistrationTest extends TestCase
{
    private array $user;

    // Runs BEFORE every individual test method
    protected function setUp(): void
    {
        parent::setUp(); // Always good practice to call parent setup

        /**
         * Override the configuration at runtime
         */
        Config::set('cognito.registration_enabled', true);
        Config::set('cognito.allow_phone_number', false);
        Config::set('cognito.force_new_user_password', false);
        Config::set('cognito.mfa_setup', 'OFF');
        Config::set('cognito.mfa_type', ['SOFTWARE_TOKEN_MFA']);
        Config::set('cognito.add_user_delivery_mediums', 'EMAIL');

        // Create a unique name and email for the test
        $name = 'Testbench Register Temp User';
        $email = 'ellaisys+tb_register_tmp_' . rand(1000, 9999) . '@gmail.com';

        $this->user = [
            'name' => $name,
            'email' => $email
        ];
    } //Function ends

    /**
     * Test that the registration page is accessible.
     */
    #[Test]
    public function test_web_registration_page(): void
    {
        $this->get(route('cognito.form.register'))
            ->assertStatus(200)
            ->assertSeeText('Register');
    } //Function ends

    /**
     * Test that the registration action works correctly without providing a
     * phone number and password.
     */
    #[Test]
    #[Depends('test_web_registration_page')]
    public function test_web_registration_action_without_phone_and_password(): void
    {
        $this->post(route('cognito.action.register.submit'), $this->user)
            ->assertStatus(302)
            ->assertRedirect(route('cognito.form.register.verify'));
    } //Function ends

    /**
     * Test that the registration action works correctly with a phone number
     * but without providing a password.
     */
    #[Test]
    #[Depends('test_web_registration_page')]
    public function test_web_registration_action_with_phone_without_password(): void
    {
        Config::set('cognito.allow_phone_number', true);

        $user = array_merge($this->user, [
            'phone' => '+1234567890',
        ]);

        $this->post(route('cognito.action.register.submit'), $user)
            ->assertStatus(302)
            ->assertRedirect(route('cognito.form.register.verify'));
    } //Function ends

    /**
     * Test that the registration action works correctly with a phone number
     * and providing a password.
     */
    #[Test]
    #[Depends('test_web_registration_page')]
    public function test_web_registration_action_with_phone_and_password(): void
    {
        Config::set('cognito.allow_phone_number', true);
        Config::set('cognito.force_new_user_password', true);

        $user = array_merge($this->user, [
            'phone' => '+1234567890',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!'
        ]);

        $this->post(route('cognito.action.register.submit'), $user)
            ->assertStatus(302)
            ->assertRedirect(route('cognito.form.register.verify'));
    } //Function ends

} //Class ends
