<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Exception;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;

use Ellaisys\Cognito\Tests\TestCase;

use Exception;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class AwsCognitoExceptionTest extends TestCase
{
    /**
     * Test that the AwsCognitoException maps AWS error codes correctly.
     */
    #[Test]
    #[DataProvider('AwsErrorCodeProvider')]
    public function test_it_maps_aws_error_codes(string $awsErrorCode, string $expectedCode): void
    {
        $previous = $this->createMock(CognitoIdentityProviderException::class);
        $previous->method('getAwsErrorCode')->willReturn($awsErrorCode);

        $exception = new AwsCognitoException('ignored', $previous);

        $this->assertSame($expectedCode, $exception->getMessage());
        $this->assertSame(400, $exception->getStatusCode());
    } //Function ends

    /**
     * Test that the AwsCognitoException can be created using the factory constructor.
     */
    #[Test]
    public function test_it_uses_factory_constructor(): void
    {
        $previous = $this->createMock(CognitoIdentityProviderException::class);
        $previous->method('getAwsErrorCode')->willReturn('NotAuthorizedException');

        $exception = AwsCognitoException::create($previous);

        $this->assertSame(AwsCognitoException::COGNITO_AUTH_USER_UNAUTHORIZED, $exception->getMessage());
        $this->assertSame(400, $exception->getStatusCode());
        $this->assertSame($previous, $exception->getPrevious());
    } //Function ends

    /**
     * Data provider for test_it_maps_aws_error_codes_correctly
     */
    public static function AwsErrorCodeProvider(): array
    {
        return [
            'password reset required' => ['PasswordResetRequiredException', AwsCognitoException::COGNITO_AUTH_USER_RESET_PASS],
            'not authorized' => ['NotAuthorizedException', AwsCognitoException::COGNITO_AUTH_USER_UNAUTHORIZED],
            'user not found' => ['UserNotFoundException', AwsCognitoException::COGNITO_USER_INVALID],
            'username exists' => ['UsernameExistsException', AwsCognitoException::COGNITO_AUTH_USERNAME_EXISTS],
            'invalid code' => ['CodeMismatchException', AwsCognitoException::COGNITO_AUTH_CODE_INVALID],
            'expired code' => ['ExpiredCodeException', AwsCognitoException::COGNITO_AUTH_CODE_INVALID],
            'throttle limit exceeded' => ['LimitExceededException', AwsCognitoException::COGNITO_THROTTLING_LIMIT],
            'too many requests' => ['TooManyRequestsException', AwsCognitoException::COGNITO_THROTTLING_LIMIT],
            'invalid webauthn' => ['WebAuthnOriginNotAllowedException', AwsCognitoException::COGNITO_WEB_AUTH_INVALID],
            'enable software token mfa' => ['EnableSoftwareTokenMFAException', AwsCognitoException::COGNITO_MFA],
            'software token mfa not found' => ['SoftwareTokenMFANotFoundException', AwsCognitoException::COGNITO_MFA],
            'invalid user pool configuration' => ['InvalidUserPoolConfigurationException', AwsCognitoException::COGNITO_AUTH_POOL_CONFIG_INVALID],
            'invalid password' => ['InvalidPasswordException', AwsCognitoException::COGNITO_INVALID_PASSWORD],
            'invalid parameter' => ['InvalidParameterException', 'InvalidParameterException'],
            'resource not found' => ['ResourceNotFoundException', 'ResourceNotFoundException'],
            'internal error' => ['InternalErrorException', 'InternalErrorException']
        ];
    } //Function ends
} // Class ends
