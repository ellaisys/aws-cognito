<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Feature\API;

use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\Group;

use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;
use Ellaisys\Cognito\Tests\Traits\AuthenticationTrait;

#[Group('api'), Group('login'), Group('logout'), Group('feature')]
class LogoutControllerApiTest extends ApiTestCase
{
    use AwsCognitoTrait;
    use AuthenticationTrait;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        /**
         * Override the configuration at runtime
         */
        Config::set('cognito.mfa_setup', 'OFF');
        Config::set('cognito.mfa_type', ['SOFTWARE_TOKEN_MFA']);

        // Authenticate the user before running the tests
        $this->authenticateApi();
    } //Function ends

    /**
     * Test that the logout routes require authentication.
     */
    #[Test]
    public function test_logout_routes_require_authentication(): void
    {
        $this->assertUnauthenticated('PUT', '/logout');
        $this->assertUnauthenticated('PUT', '/logout/forced');
    } //Function ends

    /**
     * Test that the logout route works with valid headers.
     */
    #[Test]
    public function test_logout_route_with_valid_headers(): void
    {
        $response = $this->withAccessTokenHeaders()
            ->putJson($this->apiPath('/logout'));
        $this->assertSuccess($response);
    } //Function ends

    /**
     * Test that the logout route works with valid headers.
     */
    #[Test]
    #[Depends('test_logout_route_with_valid_headers')]
    public function test_forced_logout_route_with_valid_headers(): void
    {
        $response = $this->withAccessTokenHeaders()
            ->putJson($this->apiPath('/logout/forced'));
        $this->assertSuccess($response);
    } //Function ends

} //Class ends
