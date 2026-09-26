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

use Mockery;
use App\Models\User;
use Throwable;
use Ellaisys\Cognito\Exceptions\AwsCognitoException;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;
use Ellaisys\Cognito\Exceptions\InvalidUserException;
use Ellaisys\Cognito\Exceptions\NoTokenException;
use Ellaisys\Cognito\Exceptions\Handler;
use Ellaisys\Cognito\Tests\TestCase;

use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;

use Exception;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use Aws\Command;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class CustomExceptionHandlerTest extends TestCase
{
    const TEST_EXCEPTION_ROUTE = '/test-exception';
    const NOT_FOUND_MESSAGE = 'Not Found';

    /**
     * Test exceptions that are mapped to a JSON response.
     */
    #[Test]
    #[DataProvider('jsonExceptionProvider')]
    public function test_exception_returns_expected_json_response(
        Throwable $exception, int $expectedStatusCode,
        string $expectedMessage): void
    {
        Route::get(self::TEST_EXCEPTION_ROUTE, function () use ($exception) {
            throw $exception;
        });

        $response = $this->getJson(self::TEST_EXCEPTION_ROUTE);

        $response->assertStatus($expectedStatusCode);

        $response->assertJson([
            'message' => $expectedMessage,
        ]);
    } // Function ends

    /**
     * Data provider for JSON exception responses.
     */
    public static function jsonExceptionProvider(): array
    {
        $modelNotFound = new ModelNotFoundException();

        return [
            'not found' => [
                new NotFoundHttpException(self::NOT_FOUND_MESSAGE),
                Response::HTTP_BAD_REQUEST,
                self::NOT_FOUND_MESSAGE,
            ],

            'http exception' => [
                new HttpException(
                    Response::HTTP_BAD_REQUEST,
                    'Bad Request'
                ),
                Response::HTTP_BAD_REQUEST,
                'Bad Request',
            ],

            'access denied' => [
                new AccessDeniedHttpException('Access Denied'),
                Response::HTTP_FORBIDDEN,
                'You do not have permission to perform this action.',
            ],

            'authentication' => [
                new AuthenticationException('Unauthenticated.'),
                Response::HTTP_UNAUTHORIZED,
                'Unauthenticated.',
            ],

            'unauthorized http exception' => [
                new UnauthorizedHttpException(
                    'Bearer',
                    'Unauthenticated.'
                ),
                Response::HTTP_UNAUTHORIZED,
                'Unauthenticated.',
            ],

            'invalid user' => [
                new InvalidUserException('Invalid user.'),
                Response::HTTP_UNAUTHORIZED,
                'Unauthenticated.',
            ],

            'no token' => [
                new NoTokenException('Token not found.'),
                Response::HTTP_UNAUTHORIZED,
                'Unauthenticated.',
            ],

            'invalid token' => [
                new InvalidTokenException('Invalid token.'),
                Response::HTTP_UNAUTHORIZED,
                'Unauthenticated.',
            ],

            'model not found' => [
                $modelNotFound,
                Response::HTTP_BAD_REQUEST,
                '',
            ],
        ];
    } // Function ends

    /**
     * Test validation exceptions.
     */
    #[Test]
    public function test_validation_exception_returns_unprocessable_entity(): void
    {
        $exception = ValidationException::withMessages([
            'email' => ['The email field is required.'],
        ]);

        Route::get(self::TEST_EXCEPTION_ROUTE, function () use ($exception) {
            throw $exception;
        });

        $response = $this->getJson(self::TEST_EXCEPTION_ROUTE);

        $response->assertStatus(
            Response::HTTP_UNPROCESSABLE_ENTITY
        );

        $response->assertJson([
            'message' => 'Data validation error',
        ]);
    } // Function ends

    /**
     * Test AWS Cognito exceptions.
     */
    #[Test]
    #[DataProvider('awsCognitoExceptionProvider')]
    public function test_aws_cognito_exception_returns_expected_response(
        string $message, int $expectedStatusCode, string $expectedMessage,
        ?Throwable $previous = null): void
    {
        $exception = new AwsCognitoException($message, $previous, [], $expectedStatusCode);

        Route::get(self::TEST_EXCEPTION_ROUTE, function () use ($exception) {
            throw $exception;
        });

        $response = $this->getJson(self::TEST_EXCEPTION_ROUTE);

        $response->assertStatus($expectedStatusCode);
    } // Function ends

    /**
     * Data provider for AWS Cognito exceptions.
     */
    public static function awsCognitoExceptionProvider(): array
    {
        $previous = new CognitoIdentityProviderException(
            'Incorrect username or password.',
            new Command('InitiateAuth'),
            [
                'code' => 'NotAuthorizedException',
                'message' => 'Incorrect username or password.',
            ]
        );

        return [
            'default cognito exception' => [
                'Some Cognito error',
                Response::HTTP_BAD_REQUEST,
                'Some Cognito error',
                null,
            ],

            'web auth invalid' => [
                AwsCognitoException::COGNITO_WEB_AUTH_INVALID,
                Response::HTTP_UNAUTHORIZED,
                AwsCognitoException::COGNITO_WEB_AUTH_INVALID,
                null,
            ],

            'user unauthorized' => [
                AwsCognitoException::COGNITO_AUTH_USER_UNAUTHORIZED,
                Response::HTTP_UNAUTHORIZED,
                AwsCognitoException::COGNITO_AUTH_USER_UNAUTHORIZED,
                $previous,
            ],

        ];
    } // Function ends

    /**
     * Test generic exceptions when debug mode is enabled.
     */
    #[Test]
    public function test_generic_exception_returns_debug_response_when_debug_is_enabled(): void
    {
        config(['app.debug' => true]);

        Route::get(self::TEST_EXCEPTION_ROUTE, function () {
            throw new Exception('Something went wrong');
        });

        $response = $this->getJson(self::TEST_EXCEPTION_ROUTE);

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

        $response->assertJson([
            'error' => 'Something went wrong',
        ]);

        $response->assertJsonStructure([
            'error',
            'trace',
        ]);
    } // Function ends

    /**
     * Test generic exceptions when debug mode is disabled.
     */
    #[Test]
    public function test_generic_exception_returns_default_response_when_debug_is_disabled(): void
    {
        config(['app.debug' => false]);

        Route::get(self::TEST_EXCEPTION_ROUTE, function () {
            throw new Exception('Something went wrong');
        });

        $response = $this->getJson(self::TEST_EXCEPTION_ROUTE);

        $response->assertStatus(Response::HTTP_INTERNAL_SERVER_ERROR);

        $response->assertJson([
            'message' => 'Something went wrong. Please try again later.',
        ]);
    } // Function ends

    /**
     * Test unauthorized exceptions redirect to login page.
     */
    #[Test]
    public function test_unauthorized_exception_redirects_to_login(): void
    {
        Route::get(self::TEST_EXCEPTION_ROUTE, function () {
            throw new AuthenticationException('Authentication required.');
        });

        $response = $this->get(self::TEST_EXCEPTION_ROUTE);

        $response->assertRedirect();

        $response->assertSessionHas('status', 'error');
        $response->assertSessionHas('message', 'Unauthenticated.');
    } // Function ends

    /**
     * Test non-authentication exceptions redirect back.
     */
    #[Test]
    public function test_non_authentication_exception_redirects_back(): void
    {
        $this->from('/previous-page');

        Route::get(self::TEST_EXCEPTION_ROUTE, function () {
            throw new NotFoundHttpException(self::NOT_FOUND_MESSAGE);
        });

        $response = $this->get(self::TEST_EXCEPTION_ROUTE);

        $response->assertRedirect('/previous-page');

        $response->assertSessionHas('status', 'error');
        $response->assertSessionHas('message', self::NOT_FOUND_MESSAGE);
    } // Function ends

    /**
     * Test validation errors are added to the session for web requests.
     */
    #[Test]
    public function test_validation_exception_flashes_validation_errors(): void
    {
        $this->from('/form');

        Route::get(self::TEST_EXCEPTION_ROUTE, function () {
            throw ValidationException::withMessages([
                'email' => ['The email field is required.'],
                'password' => ['The password field is required.'],
            ]);
        });

        $response = $this->get(self::TEST_EXCEPTION_ROUTE);

        $response->assertRedirect('/form');

        $response->assertSessionHas('status', 'error');
        $response->assertSessionHas(
            'message',
            'Data validation error'
        );

        $response->assertSessionHasErrors([
            'email',
            'password',
        ]);
    } // Function ends

    /**
     * Test exception logging.
     */
    #[Test]
    public function test_exception_is_logged(): void
    {
        Log::spy();

        Route::get(self::TEST_EXCEPTION_ROUTE, function () {
            throw new NotFoundHttpException(self::NOT_FOUND_MESSAGE);
        });

        $this->getJson(self::TEST_EXCEPTION_ROUTE);

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return $message === self::NOT_FOUND_MESSAGE
                    && $context['message'] === self::NOT_FOUND_MESSAGE
                    && array_key_exists('ip', $context);
            });
    } // Function ends

} // Class ends
