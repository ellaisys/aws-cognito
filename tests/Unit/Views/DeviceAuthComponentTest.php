<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Unit\Views;

use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\CoversClass;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\EncryptionTypes;
use Ellaisys\Cognito\Views\Components\DeviceAuth;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('views'), Group('component'), Group('device'), Group('auth')]
class DeviceAuthComponentTest extends TestCase
{
    /**
     * Test that the device auth component renders correctly.
     */
    #[Test]
    public function test_device_auth_component_renders_correctly(): void
    {
        /**
         * Override the configuration at runtime
         */
        Config::set('cognito.user_pool_device_enabled', true);

        // Simulate invalid session data
        session(['claim' => ['data' => [
            'NewDeviceMetadata' => [
                'DeviceKey' => 'dummy-device-key',
                'DeviceGroupKey' => 'dummy-device-group-key',
            ]
        ]]]);

        $this->blade('<x-cognito::device-auth />'
            . '@stack(\'cognito-device-auth-scripts\')')
            ->assertSee(['<script>'], false);
    } // Function ends

    /**
     * Test that the device auth component does not render when disabled.
     */
    #[Test]
    public function test_device_auth_component_does_not_render_when_disabled(): void
    {
        /**
         * Override the configuration at runtime
         */
        Config::set('cognito.user_pool_device_enabled', false);

        $this->blade('<x-cognito::device-auth />'
            . '@stack(\'cognito-device-auth-scripts\')')
            ->assertSee([''], false);
    } // Function ends
} // Class ends
