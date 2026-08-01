<?php

namespace Ellaisys\Cognito\Tests\Exceptions;

use PHPUnit\Framework\TestCase;

use Exception;
use Ellaisys\Cognito\Exceptions\DBConnectionException;

class DBConnectionExceptionTest extends TestCase
{
    public function test_it_initializes_with_defaults(): void
    {
        $exception = new DBConnectionException();

        $this->assertSame('Database Connection Error', $exception->getMessage());
        $this->assertSame(400, $exception->getCode());
    }

    public function test_it_initializes_with_custom_values_and_previous_exception(): void
    {
        $previous = new Exception('Root cause');
        $exception = new DBConnectionException('Unable to connect', $previous, 503);

        $this->assertSame('Unable to connect', $exception->getMessage());
        $this->assertSame(503, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
}
