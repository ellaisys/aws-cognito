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
class WebAuthPasskeyControllerApiTest extends ApiTestCase
{
    #[Test]
    public function test_public_passkey_challenge_route_validates_required_payload(): void
    {
        $this->assertValidationError('POST', '/login/passkey/challenge');
    }

    #[Test]
    public function test_protected_passkey_routes_require_authentication(): void
    {
        $this->assertUnauthenticated('GET', '/user/passkey/start');
        $this->assertUnauthenticated('POST', '/user/passkey/complete');
        $this->assertUnauthenticated('DELETE', '/user/passkey');
    }
}
