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
use PHPUnit\Framework\Attributes\Group;

use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;
use Ellaisys\Cognito\Tests\Traits\AuthenticationTrait;

#[Group('api'), Group('passkey'), Group('webauthn')]
class WebAuthPasskeyControllerApiTest extends ApiTestCase
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
        Config::set('cognito.allowed_auth_flows', [
            'ALLOW_REFRESH_TOKEN_AUTH',
            'ALLOW_USER_PASSWORD_AUTH',
            'ALLOW_USER_AUTH',
        ]);

        // Authenticate the user before running the tests
        $this->authenticateApi();
    } //Function ends

    /**
     * Test that the user pool client configuration allows for choice based
     * signin authentication.
     */
    #[Test]
    public function test_valid_settings_for_choice_based_signin(): void
    {
        $this->assertTrue($this->validateUserPoolClientConfig(
            Enums\CognitoAuthFlowTypes::USER_AUTH));

        // Set configuration to allow password authentication
        Config::set('cognito.allow_passkeys', true);
    } //Function ends

    #[Test]
    public function test_public_passkey_challenge_route_validates_required_payload(): void
    {
        $this->assertValidationError('POST', '/login/passkey/challenge');
    } //Function ends

    #[Test]
    public function test_protected_passkey_routes_require_authentication(): void
    {
        $this->assertUnauthenticated('GET', '/user/passkey/start');
        $this->assertUnauthenticated('POST', '/user/passkey/complete');
        $this->assertUnauthenticated('DELETE', '/user/passkey');
    } //Function ends

} //Class ends
