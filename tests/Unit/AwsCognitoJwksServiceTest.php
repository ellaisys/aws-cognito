<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;

use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Services\AwsCognitoJwksService;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

#[Group('unit'), Group('service')]
class AwsCognitoJwksServiceTest extends TestCase
{
    /**
     * @var AwsCognitoJwksService
     */
    private AwsCognitoJwksService $service;

    // Runs BEFORE every individual test method
    protected function setUp(): void
    {
        parent::setUp(); // Always good practice to call parent setup

        $this->service = app()->make(AwsCognitoJwksService::class);
    } //Function ends

    /**
     * Test that the service instance is correctly created.
     */
    #[Test]
    public function test_service_instance(): void
    {
        $this->assertInstanceOf(AwsCognitoJwksService::class, $this->service);
    } // Function ends

    /**
     * Test that the getJwks method exists and returns a valid response.
     */
    #[Test]
    public function test_service_get_method_exists(): void
    {
        $response = $this->service->getJwks();
        $this->assertNotNull($response, 'The getJwks method should return a non-null response');
        $this->assertIsArray($response, 'The getJwks method should return an array');
    } // Function ends

    /**
     * Test that the getJwks method returns a valid response after clearing the cache.
     */
    #[Test]
    public function test_service_get_method_exists_cache_cleared(): void
    {
        Cache::forget('aws-cognito:jwks-' . Config::get('cognito.user_pool_id'));

        $response = $this->service->getJwks();
        $this->assertNotNull($response, 'The getJwks method should return a non-null response');
        $this->assertIsArray($response, 'The getJwks method should return an array');
    } // Function ends

    /**
     * Test that the downloadJwks method exists and returns a valid response.
     */
    #[Test]
    public function test_service_download_method_exists(): void
    {
        $response = $this->service->downloadJwks();
        $this->assertNotNull($response, 'The downloadJwks method should return a non-null response');
        $this->assertIsString($response, 'The downloadJwks method should return a string');
    } // Function ends

} // Class ends
