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

class DeviceControllerApiTest extends ApiTestCase
{
    #[Test]
    public function test_device_routes_require_authentication(): void
    {
        $this->assertUnauthenticated('GET', '/device');
        $this->assertUnauthenticated('POST', '/device');
        $this->assertUnauthenticated('PUT', '/device/device-key');
        $this->assertUnauthenticated('DELETE', '/device/device-key');
    }
}
