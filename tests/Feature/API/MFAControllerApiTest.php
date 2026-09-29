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

#[Group('api')]
class MFAControllerApiTest extends ApiTestCase
{
    #[Test]
    public function test_user_mfa_routes_require_authentication(): void
    {
        $this->assertUnauthenticated('GET', '/user/mfa/activate');
        $this->assertUnauthenticated('POST', '/user/mfa/activate/000000');
        $this->assertUnauthenticated('POST', '/user/mfa/deactivate');
        $this->assertUnauthenticated('POST', '/user/mfa/enable');
        $this->assertUnauthenticated('POST', '/user/mfa/disable');
    }

    #[Test]
    public function test_root_mfa_routes_require_authentication(): void
    {
        $this->assertUnauthenticated('POST', '/mfa/enable');
        $this->assertUnauthenticated('POST', '/mfa/disable');
    }
}
