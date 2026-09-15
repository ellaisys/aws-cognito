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
class RegisterControllerApiTest extends ApiTestCase
{
    #[Test]
    public function test_register_route_validates_required_payload(): void
    {
        $this->assertValidationError('POST', '/register');
    }

    #[Test]
    public function test_user_invite_route_requires_authentication(): void
    {
        $this->assertUnauthenticated('POST', '/user/invite');
    }
}
