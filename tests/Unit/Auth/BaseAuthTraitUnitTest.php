<?php

/*
 * This file is part of AWS Cognito Auth solution.
 *
 * (c) EllaiSys <ellaisys@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ellaisys\Cognito\Tests\Unit\Auth;

use Illuminate\Support\Facades\Config;
use Illuminate\Http\Request;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Depends;
use PHPUnit\Framework\Attributes\DataProvider;

use Ellaisys\Cognito\Enums\CognitoAuthFlowTypes;
use Ellaisys\Cognito\Tests\TestCase;
use Ellaisys\Cognito\Auth\EncryptionTypes;
use Ellaisys\Cognito\Auth\BaseAuthTrait;
use Ellaisys\Cognito\Tests\Support\BaseAuthTraitFixture;

use Exception;
use Symfony\Component\HttpKernel\Exception\HttpException;

#[Group('web'), Group('unit'), Group('auth-trait')]
#[CoversTrait(BaseAuthTrait::class)]
class BaseAuthTraitUnitTest extends TestCase
{
    private BaseAuthTraitFixture $fixture;
    private Request $requestJson;

    // Runs before each test method
    protected function setUp(): void
    {
        parent::setUp();

        // Set config values
        Config::set('cognito.allow_phone_number', false);
        Config::set('cognito.mfa_setup', 'OFF');
        Config::set('cognito.mfa_type', ['SOFTWARE_TOKEN_MFA']);
        Config::set('cognito.desired_delivery_mediums', ['EMAIL']);

        // Create a new fixture
        $this->fixture = new BaseAuthTraitFixture();

        // Create a Json request
        $this->requestJson = Request::create('/', 'POST', [], [], [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ]
        );
    } //Function ends

    /**
     * Test that the fixture is an instance of BaseAuthTraitFixture.
     */
    #[Test]
    public function test_fixture_is_instance_of_base_auth_trait_fixture(): void
    {
        $this->assertInstanceOf(BaseAuthTraitFixture::class, $this->fixture);
    } // Function ends

    /**
     * Test getting and setting the controller action flag.
     */
    #[Test]
    public function test_get_set_controller_action(): void
    {
        $this->fixture->setIsControllerAction(true);
        $this->assertTrue($this->fixture->isControllerAction);
    } // Function ends

    /**
     * Test getting and setting the JSON response flag.
     */
    #[Test]
    public function test_get_set_json_response(): void
    {
        $this->fixture->setIsJsonResponse(true);
        $this->assertTrue($this->fixture->isJsonResponse);
        $this->assertTrue($this->fixture->getIsJsonResponse(request()));

        $this->fixture->setIsJsonResponse(false);
        $this->assertFalse($this->fixture->isJsonResponse);
        $this->assertFalse($this->fixture->getIsJsonResponse(request()));

        $this->fixture->setIsJsonResponse(false);
        $this->assertFalse($this->fixture->isJsonResponse);
        $this->assertTrue($this->fixture->getIsJsonResponse($this->requestJson));
    } // Function ends

    /**
     * Test getting and setting the raise exception flag.
     */
    #[Test]
    public function test_get_set_raise_exception(): void
    {
        $this->fixture->setIsRaiseException(true);
        $this->assertTrue($this->fixture->isRaiseException);
    } // Function ends

    /**
     * Test getting the guard.
     */
    #[Test]
    public function test_get_guard(): void
    {
        $this->assertEquals('web', $this->fixture->getGuard(request()));
        $this->assertEquals('api', $this->fixture->getGuard($this->requestJson));
    } // Function ends

    /**
     * Test getting and setting the redirect path.
     */
    #[Test]
    public function test_get_set_redirect_path(): void
    {
        $this->fixture->setRedirectPath('some-path');
        $this->assertEquals('some-path', $this->fixture->redirectTo);

        $this->fixture->redirectTo = 'another-path';
        $this->assertEquals('another-path', $this->fixture->redirectPath());

        $this->fixture->redirectTo = null;
        $this->assertEquals('alternate-path', $this->fixture->redirectPath('alternate-path'));

        $this->fixture->redirectTo = null;
        $this->assertEquals(config('cognito.routes.web.login_page'), $this->fixture->redirectPath());
    } // Function ends

    /**
     * Test getting and setting the redirect path exception.
     */
    #[Test]
    public function test_get_set_redirect_path_exception(): void
    {
        Config::set('cognito.routes.web.login_page', '');
        $this->fixture->redirectTo = null;
        $this->assertEquals(config('cognito.routes.web.login_page'), $this->fixture->redirectPath());
    } // Function ends

    /**
     * Test getting the local provider model.
     */
    #[Test]
    public function test_get_local_provider_model(): void
    {
        $model = $this->fixture->getLocalProviderModel();
        $this->assertNotNull($model);
    } // Function ends

    /**
     * Test getting data from query parameters.
     */
    #[Test]
    #[DataProvider('successQueryParamDataProvider')]
    public function test_get_data_from_query_param(array $queryParams,
        ?string $encType = null, ?bool $isEmail = false): void
    {
        // Get the key of the array
        $key = array_key_first($queryParams);
        $value = $queryParams[$key];

        // Convert the encryption type string to an EncryptionTypes enum instance
        $encType = $encType ? EncryptionTypes::from($encType) : EncryptionTypes::DEFAULT;

        // If the encryption type is BASE64_ENCODE, encode the query parameter value
        if ($encType === EncryptionTypes::BASE64_ENCODE) {
            $value = base64_decode($value);
        } // End if
        // If the encryption type is RAW_URL_ENCODE, decode the query parameter value
        if ($encType === EncryptionTypes::RAW_URL_ENCODE) {
            $value = rawurldecode($value);
        } // End if

        // Create a GET request with the provided query parameters
        $request = Request::create('/', 'GET', $queryParams, [], [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ]
        );        

        $data = $this->fixture->getDataFromQueryParam($request, $key, $encType, $isEmail);
        $this->assertSame($value, $data);
    } // Function ends

    /**
     * Data provider for test_get_data_from_query_param.
     */
    public static function successQueryParamDataProvider(): array
    {
        return [
            'default' => [['email' => 'test@example.com']],
            'default_with_null' => [['email' => 'test@example.com'], null, false],
            'default_with_default_type' => [['email' => 'test@example.com'], 'DEFAULT', false],
            'default_with_email_and_validation' => [['email' => 'test@example.com'], 'DEFAULT', true],
            'default_with_complex_email_and_validation' => [['email' => 'test+alias@example.com'], 'DEFAULT', true],
            'url_encode_with_complex_email_and_validation' => [['email' => 'test+alias@example.com'], 'URL_ENCODE', true],
            'no_encode_with_complex_email' => [['email' => 'test+alias@example.com'], 'NONE', false],
            'base64_encode_with_complex_email_and_validation' => [['email' => base64_encode('test+alias@example.com')], 'BASE64_ENCODE', true],
            'rawurl_encode_with_complex_email_and_validation' => [['email' => rawurlencode('test+alias@example.com')], 'RAW_URL_ENCODE', true],
            'base64_encode_with_url_and_no_validation' => [['email' => base64_encode('http://www.example.com')], 'BASE64_ENCODE', false],
            'rawurl_encode_with_url_and_no_validation' => [['email' => rawurlencode('http://www.example.com')], 'RAW_URL_ENCODE', false],
        ];
    } // Function ends

    /**
     * Test getting data from query parameters with wrong data.
     */
    #[Test]
    #[DataProvider('wrongQueryParamDataProvider')]
    public function test_get_data_from_query_param_with_wrong_data(array $queryParams,
        ?string $encType = null, ?bool $isEmail = false): void
    {
        // Get the key of the array
        $key = array_key_first($queryParams);
        $key = ($key === 'malformed_key') ? 'some_random_key' : $key;

        // Convert the encryption type string to an EncryptionTypes enum instance
        $encType = $encType ? EncryptionTypes::from($encType) : EncryptionTypes::DEFAULT;

        // Create a GET request with the provided query parameters
        $request = Request::create('/', 'GET', $queryParams, [], [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ]
        );        

        $data = $this->fixture->getDataFromQueryParam($request, $key, $encType, $isEmail);
        $this->assertSame(null, $data);
    } // Function ends

    /**
     * Data provider for test_get_data_from_query_param_with_wrong_data.
     */
    public static function wrongQueryParamDataProvider(): array
    {
        return [
            'malformed_key' => [['malformed_key' => 'test@exam'], null, false],
            'malformed_email_with_validation' => [['email' => 'test@exam'], null, true],
            'malformed_email_with_default_type' => [['email' => 'test@exam'], 'DEFAULT', true],
            'default_with_complex_malformed_email_and_validation' => [['email' => 'test+alias@exam'], 'DEFAULT', true],
            'url_encode_with_complex_malformed_email_and_validation' => [['email' => 'test+alias@exam'], 'URL_ENCODE', true],
            'no_encode_with_complex_malformed_email' => [['email' => 'test+alias@exam'], 'NONE', true],
            'base64_encode_with_complex_malformed_email_and_validation' => [['email' => base64_encode('test+alias@exam')], 'BASE64_ENCODE', true],
            'rawurl_encode_with_complex_malformed_email_and_validation' => [['email' => rawurlencode('test+alias@exam')], 'RAW_URL_ENCODE', true],
        ];
    } // Function ends

    /**
     * Test if the phone number is not allowed.
     */
    #[Test]
    public function test_is_phone_number_not_allowed(): void
    {
        $this->assertFalse($this->fixture->isPhoneNumberAllowed());
    } // Function ends

    /**
     * Test if the phone number is allowed.
     */
    #[Test]
    public function test_is_phone_number_allowed(): void
    {
        Config::set('cognito.allow_phone_number', true);
        $this->assertTrue($this->fixture->isPhoneNumberAllowed());
    } // Function ends

    /**
     * Test if the phone number is allowed.
     */
    #[Test]
    public function test_is_phone_number_allowed_mfa_enabled(): void
    {
        Config::set('cognito.allow_phone_number', false);
        Config::set('cognito.mfa_setup', 'OPTIONAL');
        Config::set('cognito.mfa_type', ['SOFTWARE_TOKEN_MFA', 'SMS_MFA']);
        $this->assertTrue($this->fixture->isPhoneNumberAllowed());
    } // Function ends

    /**
     * Test if the phone number is allowed.
     */
    #[Test]
    public function test_is_phone_number_allowed_with_delivery_mediums(): void
    {
        Config::set('cognito.allow_phone_number', false);
        Config::set('cognito.mfa_setup', 'OFF');
        Config::set('cognito.desired_delivery_mediums', ['SMS']);
        $this->assertTrue($this->fixture->isPhoneNumberAllowed());
    } // Function ends

    /**
     * Test if the phone number is allowed.
     */
    #[Test]
    public function test_generate_random_password(): void
    {
        $password = $this->fixture->generateRandomPassword();
        $this->assertIsString($password);
        $this->assertNotEmpty($password);
        $this->assertSame(12, strlen($password));
    } // Function ends

    /**
     * Test if the Cognito flow is validated successfully.
     */
    #[Test]
    public function test_validate_cognito_flow_success(): void
    {
        $this->fixture->validateCognitoFlow(CognitoAuthFlowTypes::USER_PASSWORD_AUTH);
        $this->assertTrue(true);
    } // Function ends

    /**
     * Test if the Cognito flow throws an exception.
     */
    #[Test]
    public function test_validate_cognito_flow_exception(): void
    {
        Config::set('cognito.allowed_auth_flows', ['ALLOW_REFRESH_TOKEN_AUTH']);

        $this->expectException(Exception::class);
        $this->fixture->validateCognitoFlow(CognitoAuthFlowTypes::USER_PASSWORD_AUTH);
    } // Function ends

    /**
     * Test if getting the authenticated user throws an exception.
     */
    #[Test]
    public function test_get_authenticated_user_exception(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Invalid Cognito User');
        $this->fixture->getAuthenticatedUser(request());
    } // Function ends

    /**
     * Test if getting the access token throws an exception.
     */
    #[Test]
    public function test_get_access_token_exception(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('EXCEPTION_INVALID_TOKEN');
        $this->fixture->getAccessToken(request());
    } // Function ends

    /**
     * Test if getting the claim throws an exception.
     */
    #[Test]
    public function test_get_claim_exception(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('EXCEPTION_INVALID_CLAIM');
        $this->fixture->getClaim(request());
    } // Function ends

    /**
     * Test if getting the Cognito user throws an exception.
     */
    #[Test]
    public function test_get_cognito_user_exception(): void
    {
        $this->expectException(Exception::class);
        $this->fixture->getCognitoUser(request());
    } // Function ends

    /**
     * Test if getting the Cognito user by admin throws an exception.
     */
    #[Test]
    public function test_get_cognito_user_by_admin_exception(): void
    {
        $this->expectException(Exception::class);
        $this->fixture->getCognitoUserByAdmin(request()->merge(['username' => 'testuser']));
    } // Function ends

    /**
     * Test if getting the Cognito user by admin throws an exception.
     */
    #[Test]
    public function test_get_cognito_user_by_admin_exception_wrong_key(): void
    {
        $this->expectException(\TypeError::class);
        $this->fixture->getCognitoUserByAdmin(request()->merge(['wrong_key' => 'testuser']));
    } // Function ends

} // Class ends
