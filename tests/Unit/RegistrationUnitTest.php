<?php

namespace Ellaisys\Cognito\Tests\Unit;

use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;

use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\RegistersUsers;

use Exception;

#[Group('web'), Group('register')]
class RegistrationUnitTest extends TestCase
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
        Config::set('cognito.registration_enabled', true);
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
     * Test that the registration action fails when phone number is allowed
     * but not provided and password is not provided.
     */
    #[Test]
    public function test_web_registration_action_not_enabled(): void
    {
        Config::set('cognito.registration_enabled', false);

        $this->post(route('cognito.action.register.submit'), $this->user)
            ->assertStatus(302)
            ->assertSessionHasErrors();
    } //Function ends

    /**
     * Test that the registration action fails when an unexpected parameter
     * is provided.
     */
    #[Test]
    public function test_web_registration_action_with_wrong_email_param(): void
    {
        $user = array_merge($this->user, [
            'email' => 'not-an-email'
        ]);

        $this->post(route('cognito.action.register.submit'), $user)
            ->assertStatus(302)
            ->assertSessionHasErrors();
    } //Function ends

    /**
     * Test that the registration action fails when phone number is allowed
     * but not provided.
     */
    #[Test]
    public function test_web_registration_action_enable_phone_without_phonedata(): void
    {
        Config::set('cognito.allow_phone_number', true);

        $this->post(route('cognito.action.register.submit'), $this->user)
            ->assertStatus(302)
            ->assertSessionHasErrors();
    } //Function ends

    /**
     * Test that the registration action fails when a phone number is provided
     * with invalid phone data.
     */
    #[Test]
    public function test_web_registration_action_with_phone_and_invalid_phonedata(): void
    {
        Config::set('cognito.allow_phone_number', true);

        $user = array_merge($this->user, [
            'phone' => '+notvalid'
        ]);

        $this->post(route('cognito.action.register.submit'), $user)
            ->assertStatus(302)
            ->assertSessionHasErrors();
    } //Function ends

    /**
     * Test that the registration action fails when a phone number and password
     * is partially provided.
     */
    #[Test]
    public function test_web_registration_action_with_phone_and_password_with_partial_data(): void
    {
        Config::set('cognito.allow_phone_number', true);
        Config::set('cognito.force_new_user_password', true);

        $user = array_merge($this->user, [
            'phone' => '+1234567890'
        ]);

        $this->post(route('cognito.action.register.submit'), $user)
            ->assertStatus(302)
            ->assertSessionHasErrors();
    } //Function ends

    /**
     * Test that the registration action fails when a phone number is provided
     * and a password is provided without confirmation.
     */
    #[Test]
    public function test_negative_web_registration_action_with_phone_and_password_without_confirmation(): void
    {
        Config::set('cognito.allow_phone_number', true);
        Config::set('cognito.force_new_user_password', true);

        $user = array_merge($this->user, [
            'phone' => '+1234567890',
            'password' => 'Password123!'
        ]);

        $this->post(route('cognito.action.register.submit'), $user)
            ->assertStatus(302)
            ->assertSessionHasErrors();
    } //Function ends

    /**
     * Test that the createUserInDatastore method throws an exception.
     */
    #[Test]
    public function test_trait_exception_in_method_createUserInDatastore(): void
    {
        $class = new class {
            use RegistersUsers;
        };

        $this->expectException(Exception::class);

        $class->createUserInDatastore([
            'name' => 'Test User',
            'email' => 'testuser@example.com'
        ], []);
    } //Function ends

} //Class ends
