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

use Ellaisys\Cognito\Tests\TestCase;

use Exception;
use Ellaisys\Cognito\Exceptions\ConsoleException;

class ConsoleExceptionTest extends TestCase
{
    /**
     * Test that the ConsoleException initializes with default values.
     */
    #[Test]
    public function test_it_initializes_with_defaults(): void
    {
        $exception = new ConsoleException();

        $this->assertSame('Console command failed', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
    } //Function ends

    /**
     * Test that the ConsoleException initializes with custom values and previous exception.
     */
    #[Test]
    public function test_it_initializes_with_custom_values_and_previous_exception(): void
    {
        $previous = new Exception('Root cause');
        $exception = new ConsoleException('Unable to connect', $previous, 503);

        $this->assertSame('Unable to connect', $exception->getMessage());
        $this->assertSame(503, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    } //Function ends
} // Class ends
