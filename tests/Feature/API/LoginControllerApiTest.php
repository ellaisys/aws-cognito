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

#[Group('api')]
class LoginControllerApiTest extends ApiTestCase
{
    private static ?array $loginResponse = null;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cognito.mfa_setup', 'OFF');
        Config::set('cognito.mfa_type', ['SOFTWARE_TOKEN_MFA']);
    }

    #[Test]
    public function test_login_routes_validate_required_payload(): void
    {
        $this->assertValidationError('POST', '/login');
        $this->assertValidationError('POST', '/login/srp');
        $this->assertValidationError('POST', '/login/challenge');
    }

    #[Test]
    public function test_logout_routes_require_authentication(): void
    {
        $this->assertUnauthenticated('PUT', '/logout');
        $this->assertUnauthenticated('PUT', '/logout/forced');
    }

    #[Test]
    public function test_user_can_authenticate_with_valid_credentials(): void
    {
        $credentials = $this->getValidCredentials();
        $payload = [
            'username' => $credentials['email'] ?? '',
            'password' => $credentials['password'] ?? '',
        ];

        $response = $this->postJson($this->apiPath('/login'), $payload)
            ->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonStructure([
                'data' => [
                    'AccessToken',
                    'RefreshToken',
                    'IdToken',
                ],
            ]);

        self::$loginResponse = $response->json();
        $this->assertNotEmpty(self::$loginResponse['data']['AccessToken'] ?? null);
    }

    #[Test]
    #[Depends('test_user_can_authenticate_with_valid_credentials')]
    public function test_logout_route_succeeds_with_access_token_header(): void
    {
        $accessToken = self::$loginResponse['data']['AccessToken'] ?? null;
        $this->assertNotEmpty($accessToken, 'Missing access token from login response.');

        $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->putJson($this->apiPath('/logout'))
            ->assertStatus(200)
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('data.message', 'Successfully logged out');
    }

    #[Test]
    public function test_user_cannot_authenticate_with_invalid_credentials(): void
    {
        $credentials = $this->getInvalidCredentials();
        $payload = [
            'username' => $credentials['email'] ?? '',
            'password' => $credentials['password'] ?? '',
        ];

        $this->postJson($this->apiPath('/login'), $payload)
            ->assertStatus(401)
            ->assertJsonPath('status', 'error')
            ->assertJsonStructure([
                'error' => [
                    'code',
                    'message',
                ],
            ]);
    }
}
