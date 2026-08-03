<?php

namespace Ellaisys\Cognito\Tests\Exception;

use PHPUnit\Framework\Attributes\DataProvider;

use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Exceptions\NoTokenException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;
use Ellaisys\Cognito\Exceptions\NoLocalUserException;

class HttpExceptionConstructorsTest extends TestCase
{
    #[DataProvider('exceptionProvider')]
    public function test_it_sets_default_http_status_and_message(string $exceptionClass, int $expectedStatusCode, string $expectedMessage): void
    {
        $exception = new $exceptionClass();

        $this->assertSame($expectedStatusCode, $exception->getStatusCode());
        $this->assertSame($expectedMessage, $exception->getMessage());
    }

    public static function exceptionProvider(): array
    {
        return [
            'NoTokenException' => [NoTokenException::class, 401, 'Authentication token not provided'],
            'InvalidUserException' => [InvalidUserException::class, 400, 'Invalid Cognito User'],
            'InvalidTokenException' => [InvalidTokenException::class, 401, 'Invalid Authentication Token'],
            'NoLocalUserException' => [NoLocalUserException::class, 401, 'User does not exist locally.'],
        ];
    }
}
