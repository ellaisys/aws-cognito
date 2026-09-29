<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Unit;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Firebase\JWT\JWT;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;

use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Validators\AwsCognitoTokenValidator;
use Ellaisys\Cognito\Tests\Traits\AwsCognitoTrait;
use Ellaisys\Cognito\Tests\Traits\AuthenticationTrait;

use Exception;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

#[Group('unit'), Group('validator')]
class AwsCognitoTokenValidatorTest extends TestCase
{
    use AwsCognitoTrait;
    use AuthenticationTrait;

    /**
     * @var AwsCognitoTokenValidator
     */
    private AwsCognitoTokenValidator $validator;

    // Runs BEFORE every individual test method
    protected function setUp(): void
    {
        parent::setUp(); // Always good practice to call parent setup

        // Authenticate the user before running the tests
        $this->authenticateWeb();

        $this->validator = new AwsCognitoTokenValidator();
    } //Function ends

    /**
     * Test that the check method throws an exception for a token with an
     * invalid structure.
     */
    #[Test]
    public function test_check_throws_exception_for_token_with_invalid_structure(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Invalid Authentication Token');

        $this->validator->check('invalid-token');
    } // Function ends

    /**
     * Test that the check method throws an exception for a malformed token.
     */
    #[Test]
    public function test_check_throws_exception_for_malformed_token(): void
    {
        $this->expectException(InvalidTokenException::class);
        $this->expectExceptionMessage('Malformed token');

        $this->validator->check('header..signature');
    } // Function ends

    /**
     * Test that the check method returns a valid token for a well-formed token.
     */
    #[Test]
    public function test_check_returns_valid_token_for_well_formed_token(): void
    {
        $claim = self::$claim ?? null;
        $token = $this->validator->check($claim['token']);
        $this->assertNotNull($token);
        $this->assertIsString($token);
    } // Function ends

} // Class ends
