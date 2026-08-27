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

use Exception;

trait AuthenticationTrait
{
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
