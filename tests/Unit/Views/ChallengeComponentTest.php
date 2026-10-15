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
use Ellaisys\Cognito\Views\Components\Challenge;

use Exception;
use Illuminate\Validation\ValidationException;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('views'), Group('component'), Group('challenge')]
#[CoversClass(Challenge::class)]
class ChallengeComponentTest extends TestCase
{
    /**
     * Test that the challenge component renders correctly.
     */
    #[Test]
    public function test_challenge_component_renders_correctly(): void
    {
        $this->blade('<x-cognito::challenge />')
            ->assertSee(['<input type="hidden" name="_token"'], false);
    } // Function ends

    /**
     * Test that the challenge component throws an exception with invalid session data.
     */
    #[Test]
    public function test_challenge_component_with_invalid_session_data(): void
    {
        // Simulate invalid session data
        session(['data' => ['abcd' => 'efgh']]);

        $this->expectException(HttpException::class);
        $this->blade('<x-cognito::challenge :challenge-form-name="\'auth-challenge-form\'" />');
    } // Function ends

    /**
     * Test that the challenge component renders correctly with session data.
     */
    #[Test]
    #[DataProvider('sessionDataProvider')]
    public function test_challenge_component_with_session_data(
        array $data): void
    {
        // Simulate a custom username in the session
        session($data);

        $this->blade('<x-cognito::challenge :challenge-form-name="\'auth-challenge-form\'" />')
            ->assertSee(['<input type="hidden" name="_token"'], false);
    } // Function ends

    /**
     * Data provider for session data tests.
     */
    public static function sessionDataProvider(): array
    {
        return [
            'some challenge session data' => [[
                'data' => [
                    'status' => 'challenge',
                    'challenge_name' => 'NONE',
                ]],
            ],
            'device auth challenge session data' => [[
                'data' => [
                    'status' => 'challenge',
                    'username' => 'some_username',
                    'session_token' => 'some_session_token',
                    'challenge_name' => 'DEVICE_SRP_AUTH',
                    'challenge_params' => [
                        'USER_ID_FOR_SRP' => 'some_user_id',
                        'CODE_DELIVERY_DELIVERY_MEDIUM' => 'EMAIL',
                        'CODE_DELIVERY_DESTINATION' => 'user@example.com',
                    ],
                ]]
            ],            
        ];
    } // Function ends
} // Class ends
