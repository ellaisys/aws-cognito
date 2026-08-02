<?php

namespace Ellaisys\Cognito\Tests\Exception;

use PHPUnit\Framework\Attributes\DataProvider;

//use Ellaisys\Cognito\Tests\TestCase;
use PHPUnit\Framework\TestCase;

use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class AwsCognitoExceptionTest extends TestCase
{
    #[DataProvider('AwsErrorCodeProvider')]
    public function test_it_maps_aws_error_codes(string $awsErrorCode, string $expectedCode): void
    {
        $previous = $this->createMock(CognitoIdentityProviderException::class);
        $previous->method('getAwsErrorCode')->willReturn($awsErrorCode);

        $exception = new AwsCognitoException('ignored', $previous);

        $this->assertSame($expectedCode, $exception->getMessage());
        $this->assertSame(400, $exception->getStatusCode());
    }

    public function test_it_uses_factory_constructor(): void
    {
        $previous = $this->createMock(CognitoIdentityProviderException::class);
        $previous->method('getAwsErrorCode')->willReturn('NotAuthorizedException');

        $exception = AwsCognitoException::create($previous);

        $this->assertSame(AwsCognitoException::COGNITO_AUTH_USER_UNAUTHORIZED, $exception->getMessage());
        $this->assertSame(400, $exception->getStatusCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

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
            'unknown passthrough' => ['SomeUnexpectedError', 'SomeUnexpectedError'],
        ];
    }
}
