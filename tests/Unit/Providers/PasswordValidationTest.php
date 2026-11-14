<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Unit\Validators;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DataProvider;

use Mockery;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\AwsCognitoUserPool;

use Exception;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

#[Group('unit'), Group('validator'), Group('password')]
class PasswordValidationTest extends TestCase
{
    // Runs BEFORE every individual test method
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    } //Function ends

    /**
     * Test that the password rule accepts valid passwords.
     */
    #[Test]
    #[DataProvider('getValidPasswordDataProvider')]
    public function test_password_rule_accepts_valid_passwords(string $password,
        ?array $policy = null): void
    {
        if (!empty($policy)) {
            Config::set('cognito.password_policy', $policy);
        } // End if

        $validator = Validator::make(
            ['password' => $password],
            ['password' => [Password::defaults()]]
        );

        $this->assertTrue($validator->passes());
    } // Function ends

    /**
     * Data provider for valid passwords.
     */
    public static function getValidPasswordDataProvider(): array
    {
        return [
            'valid password 1' => ['Strong@123'],
            'valid password 2' => ['Another@1234'],
        ];
    } // Function ends

    /**
     * Test that the password rule rejects invalid passwords.
     */
    #[Test]
    #[DataProvider('getInvalidPasswordDataProvider')]
    public function test_password_rule_rejects_invalid_passwords(string $password,
        ?array $policy = null): void
    {
        if (!empty($policy)) {
            Config::set('cognito.password_policy', $policy);
        } // End if

        $validator = Validator::make(
            ['password' => $password],
            ['password' => [Password::defaults()]]
        );

        $this->assertTrue($validator->fails());
    } // Function ends

    /**
     * Data provider for invalid passwords.
     */
    public static function getInvalidPasswordDataProvider(): array
    {
        return [
            'too short' => ['Ab@123'],
            'without letters' => ['12345678@'],
            'without mixed case' => ['strong@123'],
            'without numbers' => ['Strong@Password'],
            'without symbols' => ['StrongPassword123'],
            'with spaces' => ['Strong Password@123'],           
            'with minimum length of 8' => ['Abc@123', [
                'MinimumLength' => 8]],
            'without capital case' => ['strong@123', [
                'RequireUppercase' => true]],
            'without lowercase' => ['STRONG@123', [
                'RequireLowercase' => true,
                'RequireUppercase' => false]],
            'without symbol' => ['Strong1234', [
                'RequireSymbols' => true]],
        ];
    } // Function ends

} // Class ends
