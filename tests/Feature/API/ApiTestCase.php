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

use Ellaisys\Cognito\Tests\TestCase;

#[Group('api')]
abstract class ApiTestCase extends TestCase
{
    protected function assertValidationError(string $method, string $path, array $payload = []): void
    {
        $this->json($method, $this->apiPath($path), $payload)
            ->assertStatus(422)
            ->assertJsonPath('status', 'error');
    }

    protected function assertUnauthenticated(string $method, string $path, array $payload = []): void
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
}
