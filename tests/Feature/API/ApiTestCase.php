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

use PHPUnit\Framework\Attributes\Group;

use Illuminate\Testing\TestResponse;
use Illuminate\Testing\Fluent\AssertableJson;

use Ellaisys\Cognito\Tests\TestCase;

#[Group('api')]
abstract class ApiTestCase extends TestCase
{
    protected function assertValidationError(string $method,
        string $path, array $payload = []): void
    {
        $this->json($method, $this->apiPath($path), $payload)
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    protected function assertUnauthenticated(string $method,
        string $path, array $payload = []): void
    {
        $this->json($method, $this->apiPath($path), $payload)
            ->assertStatus(401)
            ->assertJsonPath('status', 'error');
    }

    protected function apiPath(string $path): string
    {
        $prefix = trim((string) config('cognito.api_prefix', 'cognito'), '/');
        $endpointPath = ltrim($path, '/');

        if ($prefix === '') {
            return '/api/' . $endpointPath;
        }

        return '/api/' . $prefix . '/' . $endpointPath;
    }

    protected function withAccessTokenHeaders(): self
    {
        $accessToken = self::$claim['data']['AccessToken'] ?? null;
        $this->assertNotEmpty($accessToken, 'Missing access token from login response.');

        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ]);
    }

    /**
     * Assert that the response indicates a successful operation.
     */
    protected function assertSuccess(TestResponse $response, int $statusCode = 200): void
    {
        $response
            ->assertStatus($statusCode)
            ->assertJsonStructure([
                'status',
                'message',
                'error',
                'data',
                'execution_time'
            ])
            ->assertJson([
                'status' => 'success',
                'error' => null,
                'data' => [],
            ]);
    } //Function ends

    /**
     * Assert that the response indicates a failed operation.
     */
    protected function assertFailure(TestResponse $response, int $statusCode = 400): void
    {
        $response
            ->assertStatus($statusCode)
            ->assertJsonStructure([
                'status',
                'message',
                'error' => [
                    'code',
                    'message',
                ],
                'data',
                'execution_time'
            ])
            ->assertJson([
                'status' => 'error',
                'data' => [],
            ]);
    } //Function ends
} //Class ends
