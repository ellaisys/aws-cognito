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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Depends;

use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;

class ForgotPasswordTest extends TestCase
{
    use AwsCognitoTrait;

    /**
     * Test that the forgot password form is accessible and returns a 200 status code.
     */
    #[Test]
    public function test_user_can_view_a_send_reset_link_form(): void
    {
        $this->get(route('cognito.form.password.forgot'))
            ->assertStatus(200)
            ->assertSeeText('Reset Password');
    } //Function ends

    /**
     * Test that the forgot password form accepts a valid email and sends a reset link.
     */
    #[Test]
    #[Depends('test_user_can_view_a_send_reset_link_form')]
    public function test_user_can_request_a_password_reset_link(): void
    {
        $credentials = $this->getValidCredentials();
        $payload = [
            'email' => $credentials['email'] ?? ''
        ];

        $this->post(route('cognito.action.password.forgot'), $payload)
            ->assertStatus(302)
            ->assertRedirect(route('cognito.form.password.reset'))
            ->assertSessionHas('status', 'success')
            ->assertSessionHasNoErrors();
    } //Function ends

    /**
     * Test that the forgot password form throws an error when an invalid email is provided.
     */
    #[Test]
    #[Depends('test_user_can_view_a_send_reset_link_form')]
    public function test_user_cannot_request_a_password_reset_link_with_invalid_email(): void
    {
        $payload = [
            'email' => 'invalid@example.com'
        ];

        $this->post(route('cognito.action.password.forgot'), $payload)
            ->assertStatus(302)
            ->assertSessionHas('status', 'error')
            ->assertSessionHasErrors(['email']);
    } //Function ends

    /**
     * Test that the forgot password form throws an error when an empty email is provided.
     */
    #[Test]
    #[Depends('test_user_can_view_a_send_reset_link_form')]
    public function test_user_cannot_request_a_password_reset_link_with_empty_email(): void
    {
        $payload = [
            'email' => ''
        ];

        $this->post(route('cognito.action.password.forgot'), $payload)
            ->assertStatus(302)
            ->assertSessionHas('status', 'error')
            ->assertSessionHasErrors(['email']);
    } //Function ends

    /**
     * Test that the forgot password form throws an error when an invalid email format is provided.
     */
    #[Test]
    #[Depends('test_user_can_view_a_send_reset_link_form')]
    public function test_user_cannot_request_a_password_reset_link_with_invalid_email_format(): void
    {
        $payload = [
            'email' => 'invalid-email-format'
        ];

        $this->post(route('cognito.action.password.forgot'), $payload)
            ->assertStatus(302)
            ->assertSessionHas('status', 'error')
            ->assertSessionHasErrors(['email']);
    } //Function ends

    /**
     * Test that the forgot password form throws an error when the email field is missing.
     */
    #[Test]
    #[Depends('test_user_can_view_a_send_reset_link_form')]
    public function test_user_cannot_request_a_password_reset_link_with_missing_email_field(): void
    {
        $payload = []; // No email field provided

        $this->post(route('cognito.action.password.forgot'), $payload)
            ->assertStatus(302)
            ->assertSessionHas('status', 'error')
            ->assertSessionHasErrors(['email']);
    } //Function ends

    /**
     * Test that the forgot password form throws an error when a non-string email is provided.
     */
    #[Test]
    #[Depends('test_user_can_view_a_send_reset_link_form')]
    public function test_user_cannot_request_a_password_reset_link_with_non_string_email(): void
    {
        $payload = [
            'email' => 12345 // Non-string email
        ];

        $this->post(route('cognito.action.password.forgot'), $payload)
            ->assertStatus(302)
            ->assertSessionHas('status', 'error')
            ->assertSessionHasErrors(['email']);
    } //Function ends

    /**
     * Test that the forgot password form throws an error when an excessively long email is provided.
     */
    #[Test]
    #[Depends('test_user_can_view_a_send_reset_link_form')]
    public function test_user_cannot_request_a_password_reset_link_with_excessively_long_email(): void
    {
        $longEmail = str_repeat('a', 300) . '@example.com'; // Excessively long email
        $payload = [
            'email' => $longEmail
        ];

        $this->post(route('cognito.action.password.forgot'), $payload)
            ->assertStatus(302)
            ->assertSessionHas('status', 'error')
            ->assertSessionHasErrors(['email']);
    } //Function ends

    /**
     * Test that the forgot password form throws an error when an invalid reset code is provided.
     */
    #[Test]
    #[Depends('test_user_can_request_a_password_reset_link')]
    public function test_user_submits_invalid_reset_code(): void
    {
        $credentials = $this->getValidCredentials();
        $payload = [
            'email' => $credentials['email'] ?? '',
            'code' => '123456', // Assuming this is an invalid code for testing
            'password' => 'NewValidPassword@123',
            'password_confirmation' => 'NewValidPassword@123',
        ];

        $this->post(route('cognito.action.password.reset'), $payload)
            ->assertStatus(302)
            ->assertSessionHas('status', 'error');
    } //Function ends

} //Class ends
