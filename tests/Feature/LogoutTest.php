<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Feature;

use Illuminate\Support\Facades\Config;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DependsExternal;

use Ellaisys\Cognito\Enums;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;
use Ellaisys\Cognito\Tests\Traits\AuthenticationTrait;

class LogoutTest extends TestCase
{
    use AwsCognitoTrait;
    use AuthenticationTrait;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        // Authenticate the user before running the tests
        $this->authenticate();
    } //Function ends

    /**
     * Test log out action
     */
    #[Test]
    public function test_logout_action(): void
    {
        $this->withSession(self::$sessionAuthenticated)
            ->get(route('cognito.logout'))
            ->assertStatus(302);
    } // Function ends

    /**
     * Test forced log out action.
     */
    #[Test]
    #[Depends('test_logout_action')]
    public function test_logout_forced_action(): void
    {
        $this->withSession(self::$sessionAuthenticated)
            ->post(route('cognito.logout_forced'))
            ->assertStatus(302);
    } // Function ends

} //Class ends
