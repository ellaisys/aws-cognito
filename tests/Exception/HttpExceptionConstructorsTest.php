<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Exceptions\NoTokenException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;

class HttpExceptionConstructorsTest extends TestCase
{
    /**
     * Test that the HTTP exceptions set default HTTP status and message.
     */
    #[Test]
    #[DataProvider('exceptionProvider')]
    public function test_it_sets_default_http_status_and_message(string $exceptionClass, int $expectedStatusCode, string $expectedMessage): void
    {
        $exception = new $exceptionClass();

        $this->assertSame($expectedStatusCode, $exception->getStatusCode());
        $this->assertSame($expectedMessage, $exception->getMessage());
    } //Function ends

    /**
     * Data provider for test_it_sets_default_http_status_and_message
     */
    public static function exceptionProvider(): array
    {
        return [
            'NoTokenException' => [NoTokenException::class, 401, 'Authentication token not provided'],
            'InvalidUserException' => [InvalidUserException::class, 400, 'Invalid Cognito User'],
            'InvalidTokenException' => [InvalidTokenException::class, 401, 'Invalid Authentication Token'],
            'NoLocalUserException' => [NoLocalUserException::class, 401, 'User does not exist locally.'],
        ];
    } //Function ends
} // Class ends
