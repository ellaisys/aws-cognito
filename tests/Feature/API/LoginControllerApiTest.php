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

class LoginControllerApiTest extends ApiTestCase
{
    #[Test]
    public function test_login_routes_validate_required_payload(): void
    {
        $this->assertValidationError('POST', '/login');
        $this->assertValidationError('POST', '/login/srp');
        $this->assertValidationError('POST', '/login/challenge');
    }

    #[Test]
    public function test_logout_routes_require_authentication(): void
    {
        $this->assertUnauthenticated('PUT', '/logout');
        $this->assertUnauthenticated('PUT', '/logout/forced');
    }
}
