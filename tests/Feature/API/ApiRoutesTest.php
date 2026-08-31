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

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;

use Ellaisys\Cognito\Tests\TestCase;

#[Group('api')]
class ApiRoutesTest extends TestCase
{
    #[Test]
    public function test_public_api_routes_validate_missing_payloads(): void
    {
        $routes = [
            '/register',
            '/register/verify',
            '/register/resend-code',
            '/login',
            '/login/srp',
            '/login/challenge',
            '/login/passkey/challenge',
            '/token/revalidate',
            '/password/forgot',
            '/password/reset',
        ];

        foreach ($routes as $route) {
            $this->postJson($this->getApiPath($route), [])
                ->assertStatus(422)
                ->assertJsonFragment(['status' => 'error']);
        } //Loop ends
    } //Function ends

    #[Test]
    public function test_public_api_get_passkey_routes_validate_missing_payloads(): void
    {
        $routes = [
            '/login/passkey/challenge',
            '/login/passkey/challenge/PASSWORD_SRP',
        ];

        foreach ($routes as $route) {
            $this->getJson($this->getApiPath($route))
                ->assertStatus(422)
                ->assertJsonFragment(['status' => 'error']);
        } //Loop ends
    } //Function ends

    #[Test]
    public function test_protected_api_routes_require_authentication(): void
    {
        $routes = [
            ['method' => 'get', 'path' => '/user/profile'],
            ['method' => 'post', 'path' => '/user/invite'],
            ['method' => 'post', 'path' => '/user/changepassword'],
            ['method' => 'get', 'path' => '/user/mfa/activate'],
            ['method' => 'post', 'path' => '/user/mfa/activate/000000'],
            ['method' => 'post', 'path' => '/user/mfa/deactivate'],
            ['method' => 'post', 'path' => '/user/mfa/enable'],
            ['method' => 'post', 'path' => '/user/mfa/disable'],
            ['method' => 'get', 'path' => '/user/passkey/start'],
            ['method' => 'post', 'path' => '/user/passkey/complete'],
            ['method' => 'delete', 'path' => '/user/passkey'],
            ['method' => 'put', 'path' => '/logout'],
            ['method' => 'put', 'path' => '/logout/forced'],
            ['method' => 'post', 'path' => '/mfa/enable'],
            ['method' => 'post', 'path' => '/mfa/disable'],
            ['method' => 'post', 'path' => '/token/refresh'],
            ['method' => 'get', 'path' => '/device'],
            ['method' => 'post', 'path' => '/device'],
            ['method' => 'put', 'path' => '/device/device-key'],
            ['method' => 'delete', 'path' => '/device/device-key'],
        ];

        foreach ($routes as $route) {
            $this->json($route['method'], $this->getApiPath($route['path']))
                ->assertStatus(401)
                ->assertJsonFragment(['status' => 'error']);
        } //Loop ends
    } //Function ends

    private function getApiPath(string $path): string
    {
        $prefix = trim((string) config('cognito.api_prefix', 'cognito'), '/');
        $endpointPath = ltrim($path, '/');

        if ($prefix === '') {
            return '/api/' . $endpointPath;
        } //End if

        return '/api/' . $prefix . '/' . $endpointPath;
    } //Function ends
} //Class ends
