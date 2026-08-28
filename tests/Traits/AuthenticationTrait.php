<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Traits;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DependsExternal;

use Ellaisys\Cognito\Enums;

use Exception;

trait AuthenticationTrait
{
    /**
     * Authenticate the user using valid credentials.
     */
    public function authenticate(): void
    {
        $this->validateUserPoolClientConfig(
            Enums\CognitoAuthFlowTypes::USER_PASSWORD_AUTH);

        // Get valid credentials for the user
        $credentials = $this->getValidCredentials();
        $payload = [
            'username' => $credentials['email'] ?? '',
            'password' => $credentials['password'] ?? '',
        ];
        $this->post(route('cognito.action.login.submit'), $payload);

        // Assert that the user is authenticated
        $this->assertAuthenticated();

        if (session()->has('claim')) {
            self::$sessionAuthenticated = session()->all();
            self::$claim = session('claim');
        } //End if

        $this->assertClaimIsValid();
    } //Function ends

    /**
     * Test that the claim is valid after a successful login.
     */
    public function assertClaimIsValid(): void
    {
        $this->assertNotNull(self::$claim, 'Claim is null.');
        $this->assertArrayHasKey('data', self::$claim, 'Claim does not contain the expected "data" key.');
    } //Function ends

    /**
     * Test that the claim has an access token after a successful login.
     */
    public function assertClaimHasAccessToken(): void
    {
        $this->assertArrayHasKey('AccessToken', self::$claim['data'], 'Access token is missing in the claim.');
        $this->assertNotEmpty(self::$claim['data']['AccessToken'], 'Access token is empty in the claim.');
    } //Function ends

    /**
     * Test that the claim has a refresh token after a successful login.
     */
    public function assertClaimHasRefreshToken(): void
    {
        $this->assertArrayHasKey('RefreshToken', self::$claim['data'], 'Refresh token is missing in the claim.');
        $this->assertNotEmpty(self::$claim['data']['RefreshToken'], 'Refresh token is empty in the claim.');
    } //Function ends

    /**
     * Test that the claim has an ID token after a successful login.
     */
    public function assertClaimHasIdToken(): void
    {
        $this->assertArrayHasKey('IdToken', self::$claim['data'], 'ID token is missing in the claim.');
        $this->assertNotEmpty(self::$claim['data']['IdToken'], 'ID token is empty in the claim.');
    } //Function ends

} //Trait ends
