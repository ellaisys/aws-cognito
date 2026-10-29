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
use Ellaisys\Cognito\Views\Components\PasskeyWebAuthn;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('views'), Group('component'), Group('passkey'), Group('webauthn')]
class PasskeyWebAuthnComponentTest extends TestCase
{
    /**
     * Test that the passkey webauthn component renders correctly.
     */
    #[Test]
    public function test_passkey_webauthn_component_renders_correctly(): void
    {
        /**
         * Override the configuration at runtime
         */
        Config::set('cognito.allow_passkeys', true);

        $this->blade('<x-cognito::passkey-webauthn />'
            . '@stack(\'cognito-passkey-webauthn-scripts\')')
            ->assertSee(['<script>'], false);
    } // Function ends

    /**
     * Test that the passkey webauthn component does not render when disabled.
     */
    #[Test]
    public function test_passkey_webauthn_component_does_not_render_when_disabled(): void
    {
        /**
         * Override the configuration at runtime
         */
        Config::set('cognito.allow_passkeys', false);

        $this->blade('<x-cognito::passkey-webauthn />'
            . '@stack(\'cognito-passkey-webauthn-scripts\')')
            ->assertSee([''], false);
    } // Function ends

} // Class ends
