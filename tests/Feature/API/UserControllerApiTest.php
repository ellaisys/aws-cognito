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
class UserControllerApiTest extends ApiTestCase
{
    #[Test]
    public function test_user_profile_route_requires_authentication(): void
    {
        $this->assertUnauthenticated('GET', '/user/profile');
    }
}
